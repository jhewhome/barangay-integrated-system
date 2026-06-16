using System.Diagnostics;
using System.Linq;
using System.Runtime.InteropServices;
using DocumentFormat.OpenXml.Packaging;
using DocumentFormat.OpenXml.Wordprocessing;
using Microsoft.Extensions.Caching.Memory;
using Microsoft.Extensions.Options;
using Microsoft.Extensions.Logging;
using Microsoft.Extensions.Logging.Abstractions;
using Project.Gawad.Application.Options;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Enums;

namespace Project.Gawad.Application.Services;

public class DocumentService(
    IOptions<TemplatesConfigOption> templateConfigOptions,
    IMemoryCache memoryCache,
    ILogger<DocumentService>? logger = null)
    : IDocumentService
{
    private readonly IMemoryCache _memoryCache =
        memoryCache ?? throw new ArgumentNullException(nameof(memoryCache));

    private readonly TemplatesConfigOption _templateConfigOptions =
        templateConfigOptions.Value ?? throw new ArgumentNullException(nameof(templateConfigOptions));

    private readonly ILogger<DocumentService> _logger =
        logger ?? NullLogger<DocumentService>.Instance;

    public async Task<byte[]> GenerateDocument(string id,
        BarangayDocumentType barangayDocumentType,
        IDictionary<string, string> data, CancellationToken cancellationToken = default)
    {
        var cacheKey = $"document_{id}";

        if (_memoryCache.TryGetValue(cacheKey, out byte[] fileBytes) && fileBytes?.Length > 0)
            return fileBytes;

        var templateItemConfig = GetTemplathPath(barangayDocumentType);

        if (templateItemConfig == null || string.IsNullOrEmpty(templateItemConfig.FileTemplatePath))
            throw new ApplicationException("Invalid template configuration for document type.");

        var sourceTemplatePath = Path.Combine(Directory.GetCurrentDirectory(), $"wwwroot/{templateItemConfig.FileTemplatePath}");
        if (!File.Exists(sourceTemplatePath))
        {
            _logger.LogError("Template file not found at path: {Path}", sourceTemplatePath);
            throw new FileNotFoundException("Template file not found.", sourceTemplatePath);
        }

        var templateDocxPath = Path.Combine(Directory.GetCurrentDirectory(),
            $"wwwroot/templates/{templateItemConfig.Type}_{Guid.NewGuid()}.docx");

        var templatePdfPath = templateDocxPath.Replace(".docx", ".pdf");

        try
        {
            // Copy template
            File.Copy(sourceTemplatePath, templateDocxPath);

            // Replace placeholders
            using (var doc = WordprocessingDocument.Open(templateDocxPath, true))
            {
                var body = doc.MainDocumentPart?.Document?.Body;
                if (body == null)
                {
                    _logger.LogError("Template document body is null: {Path}", templateDocxPath);
                    throw new ApplicationException("Template document is invalid.");
                }

                foreach (var text in body.Descendants<Text>())
                {
                    foreach (var keyPairData in data)
                    {
                        if (!string.IsNullOrEmpty(text.Text) && text.Text.Contains(keyPairData.Key))
                            text.Text = text.Text.Replace(keyPairData.Key, keyPairData.Value ?? string.Empty);
                    }
                }

                doc.MainDocumentPart.Document.Save();
            }

            // Convert to PDF using LibreOffice / soffice
            await ConvertOutputDocxToPdf(templateDocxPath, cancellationToken);

            if (!File.Exists(templatePdfPath))
            {
                _logger.LogError("PDF conversion did not produce output: {PdfPath}", templatePdfPath);
                throw new ApplicationException("Failed to convert document to PDF. Ensure LibreOffice (soffice) is installed and accessible.");
            }

            fileBytes = File.ReadAllBytes(templatePdfPath);

            // Cache result
            _memoryCache.Set(cacheKey, fileBytes, new MemoryCacheEntryOptions
            {
                AbsoluteExpirationRelativeToNow = TimeSpan.FromMinutes(5),
                SlidingExpiration = TimeSpan.FromMinutes(3)
            });

            return fileBytes;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error generating document for id {Id} type {Type}", id, barangayDocumentType);
            throw;
        }
        finally
        {
            try
            {
                if (File.Exists(templateDocxPath))
                    File.Delete(templateDocxPath);

                if (File.Exists(templatePdfPath))
                    File.Delete(templatePdfPath);
            }
            catch (Exception ex)
            {
                // Non-fatal cleanup failure, just log
                _logger.LogWarning(ex, "Failed to clean up temporary files for document {Id}", id);
            }
        }
    }

    private TemplateItemConfig GetTemplathPath(BarangayDocumentType barangayDocumentType)
    {
        return _templateConfigOptions.Templates.FirstOrDefault(t => t.Type == barangayDocumentType);
    }

    private async Task ConvertOutputDocxToPdf(string docxFilePathUri, CancellationToken cancellationToken)
    {
        var sofficePath = FindSofficePath();
        if (string.IsNullOrEmpty(sofficePath))
        {
            _logger.LogError("LibreOffice (soffice) executable not found. Expected to find in Program Files or in PATH.");
            throw new FileNotFoundException("LibreOffice (soffice) not found. Please install LibreOffice and ensure soffice is available in PATH or installed to Program Files.");
        }

        var outDir = Path.GetDirectoryName(docxFilePathUri) ?? Directory.GetCurrentDirectory();
        var parameters = $"--headless --convert-to pdf --outdir \"{outDir}\" \"{docxFilePathUri}\"";

        var process = new Process();
        process.StartInfo.FileName = sofficePath;
        process.StartInfo.Arguments = parameters;
        process.StartInfo.UseShellExecute = false;
        process.StartInfo.RedirectStandardOutput = true;
        process.StartInfo.RedirectStandardError = true;
        process.StartInfo.CreateNoWindow = true;

        _logger.LogInformation("Running soffice: {Cmd} {Args}", sofficePath, parameters);

        process.Start();

        var stdOut = await process.StandardOutput.ReadToEndAsync(cancellationToken);
        var stdErr = await process.StandardError.ReadToEndAsync(cancellationToken);

        await process.WaitForExitAsync(cancellationToken);

        if (process.ExitCode != 0)
        {
            _logger.LogError("soffice exited with code {Code}. stdout: {Out} stderr: {Err}", process.ExitCode, stdOut, stdErr);
            throw new ApplicationException("Document conversion failed. See logs for details.");
        }
    }

    private string? FindSofficePath()
    {
        // Common Windows locations
        if (RuntimeInformation.IsOSPlatform(OSPlatform.Windows))
        {
            var candidates = new[]
            {
                @"C:\Program Files\LibreOffice\program\soffice.exe",
                @"C:\Program Files (x86)\LibreOffice\program\soffice.exe"
            };

            foreach (var c in candidates)
                if (File.Exists(c))
                    return c;

            // Try to locate via where.exe (PATH)
            try
            {
                var start = Process.Start(new ProcessStartInfo("where", "soffice")
                {
                    RedirectStandardOutput = true,
                    UseShellExecute = false,
                    CreateNoWindow = true
                });

                var output = start?.StandardOutput.ReadToEnd() ?? string.Empty;
                start?.WaitForExit();

                var first = output?.Split(new[] { '\r', '\n' }, StringSplitOptions.RemoveEmptyEntries).FirstOrDefault();
                if (!string.IsNullOrWhiteSpace(first) && File.Exists(first))
                    return first;
            }
            catch
            {
                // ignore and fallthrough
            }

            return null;
        }

        // Linux / macOS - assume 'soffice' is in PATH
        if (RuntimeInformation.IsOSPlatform(OSPlatform.Linux) || RuntimeInformation.IsOSPlatform(OSPlatform.OSX))
        {
            try
            {
                var start = Process.Start(new ProcessStartInfo("which", "soffice")
                {
                    RedirectStandardOutput = true,
                    UseShellExecute = false,
                    CreateNoWindow = true
                });

                var output = start?.StandardOutput.ReadToEnd() ?? string.Empty;
                start?.WaitForExit();

                var first = output?.Split(new[] { '\r', '\n' }, StringSplitOptions.RemoveEmptyEntries).FirstOrDefault();
                if (!string.IsNullOrWhiteSpace(first) && File.Exists(first))
                    return first;
            }
            catch
            {
                // ignore
            }

            return "soffice"; // rely on PATH
        }

        return null;
    }
}