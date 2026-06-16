using System.Text.Json;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Options;
using Microsoft.Extensions.Configuration;
using Project.Gawad.Application.Options;
using Project.Gawad.Client.Models;
using SixLabors.ImageSharp;
using SixLabors.ImageSharp.Processing;
using SixLabors.ImageSharp.Formats.Png;

namespace Project.Gawad.Client.Controllers;

[Authorize]
public class SettingsController : Controller
{
    private readonly IWebHostEnvironment _env;
    private readonly IOptionsMonitor<OfficialSignatoryOption> _officialSignatoryOptions;
    private readonly IOptionsMonitor<VerificationUrlOption> _verificationUrlOptions;
    private readonly IConfiguration _configuration;
    private readonly ILogger<SettingsController> _logger;

    public SettingsController(
        IWebHostEnvironment env,
        IOptionsMonitor<OfficialSignatoryOption> officialSignatoryOptions,
        IOptionsMonitor<VerificationUrlOption> verificationUrlOptions,
        IConfiguration configuration,
        ILogger<SettingsController> logger)
    {
        _env = env;
        _officialSignatoryOptions = officialSignatoryOptions;
        _verificationUrlOptions = verificationUrlOptions;
        _configuration = configuration;
        _logger = logger;
    }
    
    private OfficialSignatoryOption CurrentOptions => _officialSignatoryOptions.CurrentValue;
    private VerificationUrlOption CurrentVerificationUrl => _verificationUrlOptions.CurrentValue;

    [HttpGet]
    public IActionResult Index()
    {
        // Official Signatory Settings page
        var model = new SettingsViewModel
        {
            SignatoryName = CurrentOptions.SignatoryName,
            SignatoryPosition = CurrentOptions.SignatoryPosition,
            SignaturePath = CurrentOptions.SignaturePath,
            VerificationBaseUrl = CurrentVerificationUrl.BaseUrl
        };
        return View(model);
    }

    [HttpGet]
    public IActionResult VerificationUrl()
    {
        // QR Code Verification URL Settings page
        var model = new SettingsViewModel
        {
            SignatoryName = CurrentOptions.SignatoryName,
            SignatoryPosition = CurrentOptions.SignatoryPosition,
            SignaturePath = CurrentOptions.SignaturePath,
            VerificationBaseUrl = CurrentVerificationUrl.BaseUrl
        };
        return View(model);
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> UpdateOfficialSignatory([FromForm] SettingsViewModel model, IFormFile? signatureFile)
    {
        // Log received values for debugging
        _logger.LogInformation("Received form values - Name: '{SignatoryName}', Position: '{SignatoryPosition}', BaseUrl: '{VerificationBaseUrl}'", 
            model?.SignatoryName ?? "NULL", model?.SignatoryPosition ?? "NULL", model?.VerificationBaseUrl ?? "NULL");
        
        if (!ModelState.IsValid)
        {
            _logger.LogWarning("Model state is invalid. Errors: {Errors}", 
                string.Join(", ", ModelState.SelectMany(x => x.Value.Errors.Select(e => $"{x.Key}: {e.ErrorMessage}"))));
            TempData["Error"] = "Invalid input. Please check the form.";
            return View("Index", model ?? new SettingsViewModel
            {
                SignatoryName = CurrentOptions.SignatoryName,
                SignatoryPosition = CurrentOptions.SignatoryPosition,
                SignaturePath = CurrentOptions.SignaturePath,
                VerificationBaseUrl = CurrentVerificationUrl.BaseUrl
            });
        }

        try
        {
            // Ensure we have valid values
            if (string.IsNullOrWhiteSpace(model.SignatoryName))
            {
                ModelState.AddModelError(nameof(model.SignatoryName), "Signatory Name is required.");
                TempData["Error"] = "Signatory Name is required.";
                return View("Index", model);
            }
            
            if (string.IsNullOrWhiteSpace(model.SignatoryPosition))
            {
                ModelState.AddModelError(nameof(model.SignatoryPosition), "Signatory Position is required.");
                TempData["Error"] = "Signatory Position is required.";
                return View("Index", model);
            }

            // Log received values for debugging
            _logger.LogInformation("Updating signatory settings - Name: '{SignatoryName}', Position: '{SignatoryPosition}'", 
                model.SignatoryName, model.SignatoryPosition);

            // Validate that signature is provided (either from file or existing)
            if (signatureFile == null || signatureFile.Length == 0)
            {
                // If no new file, keep existing signature path if available
                var existingSignaturePath = CurrentOptions?.SignaturePath;
                if (string.IsNullOrWhiteSpace(existingSignaturePath))
                {
                    ModelState.AddModelError("signatureFile", "Please provide an official signature. You can sign using the signature pad or upload a signature image.");
                    TempData["Error"] = "Please provide an official signature.";
                    return View("Index", model);
                }
                model.SignaturePath = existingSignaturePath;
            }
            else
            {
                // Save signature image if uploaded
                var signaturePath = await SaveOfficialSignatureAsync(signatureFile);
                if (!string.IsNullOrWhiteSpace(signaturePath))
                {
                    model.SignaturePath = signaturePath;
                }
                else
                {
                    // Fallback to existing if save failed
                    model.SignaturePath = CurrentOptions?.SignaturePath;
                }
            }

            // Update appsettings.json - only OfficialSignatory section (preserve VerificationUrl)
            await UpdateOfficialSignatorySectionAsync(model.SignatoryName, model.SignatoryPosition, model.SignaturePath);

            // Reload configuration to get the latest values
            if (_configuration is IConfigurationRoot configurationRoot)
            {
                configurationRoot.Reload();
            }

            // Log saved values for debugging
            _logger.LogInformation("Settings saved successfully - Name: {SignatoryName}, Position: {SignatoryPosition}, SignaturePath: {SignaturePath}", 
                model.SignatoryName, model.SignatoryPosition, model.SignaturePath);

            TempData["Success"] = "Official signatory settings updated successfully!";
            
            // Get the latest values from configuration after reload
            var latestOptions = _configuration.GetSection("OfficialSignatory").Get<OfficialSignatoryOption>() 
                ?? new OfficialSignatoryOption();
            var latestVerificationUrl = _configuration.GetSection("VerificationUrl").Get<VerificationUrlOption>() 
                ?? new VerificationUrlOption();
            
            // Use the updated values from the model (which are already trimmed)
            var updatedModel = new SettingsViewModel
            {
                SignatoryName = model.SignatoryName?.Trim() ?? latestOptions.SignatoryName ?? string.Empty,
                SignatoryPosition = model.SignatoryPosition?.Trim() ?? latestOptions.SignatoryPosition ?? string.Empty,
                SignaturePath = model.SignaturePath ?? latestOptions.SignaturePath,
                VerificationBaseUrl = latestVerificationUrl.BaseUrl // Preserve existing verification URL
            };
            
            _logger.LogInformation("Returning updated model to view - Name: '{SignatoryName}', Position: '{SignatoryPosition}', SignaturePath: '{SignaturePath}', BaseUrl: '{VerificationBaseUrl}'", 
                updatedModel.SignatoryName, updatedModel.SignatoryPosition, updatedModel.SignaturePath ?? "null", updatedModel.VerificationBaseUrl ?? "null");
            
            // Clear ModelState to ensure view uses the updated model values
            ModelState.Clear();
            
            // Return the updated model directly to the view instead of redirecting
            // This ensures the updated values are displayed immediately without requiring app restart
            return View("Index", updatedModel);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error updating official signatory settings");
            ModelState.AddModelError("", "Error updating settings. Please try again.");
            return View("Index", model);
        }
    }

    private async Task<string> SaveOfficialSignatureAsync(IFormFile signatureFile)
    {
        var uploadsRoot = System.IO.Path.Combine(_env.WebRootPath ?? "wwwroot", "uploads", "signatures");
        Directory.CreateDirectory(uploadsRoot);

        var ext = System.IO.Path.GetExtension(signatureFile.FileName);
        if (string.IsNullOrWhiteSpace(ext)) ext = ".png";

        // Use a fixed filename for official signature
        var safeFileName = "official_signature" + ext.ToLowerInvariant();
        var fullPath = System.IO.Path.Combine(uploadsRoot, safeFileName);

        // If existing file exists, delete it first
        if (System.IO.File.Exists(fullPath))
        {
            System.IO.File.Delete(fullPath);
        }

        // Save signature with resizing using ImageSharp
        using (var image = await Image.LoadAsync(signatureFile.OpenReadStream()))
        {
            // Resize signature to reasonable size (max 300x150 for ID cards)
            image.Mutate(x => x.Resize(new ResizeOptions
            {
                Mode = ResizeMode.Max,
                Size = new Size(300, 150)
            }));

            // Save as PNG for better quality and transparency support
            await image.SaveAsync(fullPath, new PngEncoder());
        }

        // Return relative path from wwwroot
        return $"uploads/signatures/{safeFileName}";
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> UpdateVerificationUrl([FromForm] string verificationBaseUrl)
    {
        _logger.LogInformation("Received Verification URL update - BaseUrl: '{VerificationBaseUrl}'", verificationBaseUrl ?? "NULL");
        
        try
        {
            // Update appsettings.json - only VerificationUrl section (preserve OfficialSignatory)
            await UpdateVerificationUrlSectionAsync(verificationBaseUrl);

            // Reload configuration to get the latest values
            if (_configuration is IConfigurationRoot configurationRoot)
            {
                configurationRoot.Reload();
            }

            _logger.LogInformation("Verification URL saved successfully - BaseUrl: '{BaseUrl}'", verificationBaseUrl);

            TempData["Success"] = "QR Code Verification URL updated successfully!";
            
            // Get the latest values from configuration after reload
            var latestOptions = _configuration.GetSection("OfficialSignatory").Get<OfficialSignatoryOption>() 
                ?? new OfficialSignatoryOption();
            var latestVerificationUrl = _configuration.GetSection("VerificationUrl").Get<VerificationUrlOption>() 
                ?? new VerificationUrlOption();
            
            // Return model with updated verification URL but preserve signatory settings
            var updatedModel = new SettingsViewModel
            {
                SignatoryName = latestOptions.SignatoryName,
                SignatoryPosition = latestOptions.SignatoryPosition,
                SignaturePath = latestOptions.SignaturePath,
                VerificationBaseUrl = latestVerificationUrl.BaseUrl
            };
            
            ModelState.Clear();
            return View("VerificationUrl", updatedModel);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error updating verification URL settings");
            TempData["Error"] = "Error updating verification URL. Please try again.";
            
            // Return model with current values on error
            var model = new SettingsViewModel
            {
                SignatoryName = CurrentOptions.SignatoryName,
                SignatoryPosition = CurrentOptions.SignatoryPosition,
                SignaturePath = CurrentOptions.SignaturePath,
                VerificationBaseUrl = CurrentVerificationUrl.BaseUrl
            };
            return View("VerificationUrl", model);
        }
    }

    private async Task UpdateOfficialSignatorySectionAsync(string? signatoryName, string? signatoryPosition, string? signaturePath)
    {
        var appSettingsPath = System.IO.Path.Combine(_env.ContentRootPath, "appsettings.json");
        
        if (!System.IO.File.Exists(appSettingsPath))
        {
            throw new FileNotFoundException("appsettings.json not found", appSettingsPath);
        }

        // Read existing appsettings.json
        var jsonContent = await System.IO.File.ReadAllTextAsync(appSettingsPath);
        
        // Parse JSON
        var jsonObject = System.Text.Json.Nodes.JsonObject.Parse(jsonContent);
        
        if (jsonObject == null)
        {
            throw new InvalidOperationException("Failed to parse appsettings.json");
        }

        // Update OfficialSignatory section only - preserve VerificationUrl
        var trimmedSignatoryName = (signatoryName ?? string.Empty).Trim();
        var trimmedSignatoryPosition = (signatoryPosition ?? string.Empty).Trim();
        var trimmedSignaturePath = signaturePath?.Trim();
        
        _logger.LogInformation("Updating OfficialSignatory section - Name: '{SignatoryName}', Position: '{SignatoryPosition}', SignaturePath: '{SignaturePath}'", 
            trimmedSignatoryName, trimmedSignatoryPosition, trimmedSignaturePath ?? "null");
        
        var officialSignatoryNode = new System.Text.Json.Nodes.JsonObject
        {
            ["SignatoryName"] = trimmedSignatoryName,
            ["SignatoryPosition"] = trimmedSignatoryPosition,
            ["SignaturePath"] = trimmedSignaturePath != null ? System.Text.Json.Nodes.JsonValue.Create(trimmedSignaturePath) : null
        };

        jsonObject["OfficialSignatory"] = officialSignatoryNode;
        
        // VerificationUrl section is preserved - not modified

        // Write back to file with indentation
        var options = new JsonSerializerOptions
        {
            WriteIndented = true,
            DefaultIgnoreCondition = System.Text.Json.Serialization.JsonIgnoreCondition.WhenWritingNull
        };

        var updatedJson = jsonObject.ToJsonString(options);
        await System.IO.File.WriteAllTextAsync(appSettingsPath, updatedJson);
        
        _logger.LogInformation("appsettings.json OfficialSignatory section updated successfully.");
    }

    private async Task UpdateVerificationUrlSectionAsync(string? verificationBaseUrl)
    {
        var appSettingsPath = System.IO.Path.Combine(_env.ContentRootPath, "appsettings.json");
        
        if (!System.IO.File.Exists(appSettingsPath))
        {
            throw new FileNotFoundException("appsettings.json not found", appSettingsPath);
        }

        // Read existing appsettings.json
        var jsonContent = await System.IO.File.ReadAllTextAsync(appSettingsPath);
        
        // Parse JSON
        var jsonObject = System.Text.Json.Nodes.JsonObject.Parse(jsonContent);
        
        if (jsonObject == null)
        {
            throw new InvalidOperationException("Failed to parse appsettings.json");
        }

        // Update VerificationUrl section only - preserve OfficialSignatory
        var trimmedBaseUrl = verificationBaseUrl?.Trim();
        
        _logger.LogInformation("Updating VerificationUrl section - BaseUrl: '{BaseUrl}'", trimmedBaseUrl ?? "null");
        
        var verificationUrlNode = new System.Text.Json.Nodes.JsonObject
        {
            ["BaseUrl"] = trimmedBaseUrl ?? string.Empty
        };
        
        jsonObject["VerificationUrl"] = verificationUrlNode;
        
        // OfficialSignatory section is preserved - not modified

        // Write back to file with indentation
        var options = new JsonSerializerOptions
        {
            WriteIndented = true,
            DefaultIgnoreCondition = System.Text.Json.Serialization.JsonIgnoreCondition.WhenWritingNull
        };

        var updatedJson = jsonObject.ToJsonString(options);
        await System.IO.File.WriteAllTextAsync(appSettingsPath, updatedJson);
        
        _logger.LogInformation("appsettings.json VerificationUrl section updated successfully.");
    }

    private async Task UpdateAppSettingsJsonAsync(SettingsViewModel model)
    {
        var appSettingsPath = System.IO.Path.Combine(_env.ContentRootPath, "appsettings.json");
        
        if (!System.IO.File.Exists(appSettingsPath))
        {
            throw new FileNotFoundException("appsettings.json not found", appSettingsPath);
        }

        // Read existing appsettings.json
        var jsonContent = await System.IO.File.ReadAllTextAsync(appSettingsPath);
        
        // Parse JSON using JsonDocument for better control
        using var jsonDoc = JsonDocument.Parse(jsonContent);
        var root = jsonDoc.RootElement;
        
        // Convert to JsonObject-like structure using System.Text.Json.Nodes
        var jsonObject = System.Text.Json.Nodes.JsonObject.Parse(jsonContent);
        
        if (jsonObject == null)
        {
            throw new InvalidOperationException("Failed to parse appsettings.json");
        }

        // Update OfficialSignatory section - ensure values are properly set
        // Trim whitespace and ensure non-null values
        var signatoryName = (model.SignatoryName ?? string.Empty).Trim();
        var signatoryPosition = (model.SignatoryPosition ?? string.Empty).Trim();
        var signaturePath = model.SignaturePath?.Trim();
        
        // Update VerificationUrl section
        var verificationBaseUrl = model.VerificationBaseUrl?.Trim();
        
        _logger.LogInformation("Updating appsettings.json - Name: '{SignatoryName}', Position: '{SignatoryPosition}', SignaturePath: '{SignaturePath}', BaseUrl: '{VerificationBaseUrl}'", 
            signatoryName, signatoryPosition, signaturePath ?? "null", verificationBaseUrl ?? "null");
        
        var officialSignatoryNode = new System.Text.Json.Nodes.JsonObject
        {
            ["SignatoryName"] = signatoryName,
            ["SignatoryPosition"] = signatoryPosition,
            ["SignaturePath"] = signaturePath != null ? System.Text.Json.Nodes.JsonValue.Create(signaturePath) : null
        };

        jsonObject["OfficialSignatory"] = officialSignatoryNode;
        
        // Update VerificationUrl section
        var verificationUrlNode = new System.Text.Json.Nodes.JsonObject
        {
            ["BaseUrl"] = verificationBaseUrl ?? string.Empty
        };
        
        jsonObject["VerificationUrl"] = verificationUrlNode;

        // Write back to file with indentation
        var options = new JsonSerializerOptions
        {
            WriteIndented = true,
            DefaultIgnoreCondition = System.Text.Json.Serialization.JsonIgnoreCondition.WhenWritingNull
        };

        var updatedJson = jsonObject.ToJsonString(options);
        await System.IO.File.WriteAllTextAsync(appSettingsPath, updatedJson);
        
        _logger.LogInformation("appsettings.json updated successfully. New JSON content: {JsonContent}", updatedJson);
        
        // Verify the update was successful by reading the file back
        var verifyContent = await System.IO.File.ReadAllTextAsync(appSettingsPath);
        var verifyDoc = JsonDocument.Parse(verifyContent);
        if (verifyDoc.RootElement.TryGetProperty("OfficialSignatory", out var officialSignatoryProp))
        {
            var verifyName = officialSignatoryProp.TryGetProperty("SignatoryName", out var nameProp) ? nameProp.GetString() : null;
            var verifyPosition = officialSignatoryProp.TryGetProperty("SignatoryPosition", out var positionProp) ? positionProp.GetString() : null;
            _logger.LogInformation("Verified appsettings.json OfficialSignatory - Name: '{SignatoryName}', Position: '{SignatoryPosition}'", 
                verifyName ?? "null", verifyPosition ?? "null");
        }
        
        if (verifyDoc.RootElement.TryGetProperty("VerificationUrl", out var verificationUrlProp))
        {
            var verifyBaseUrl = verificationUrlProp.TryGetProperty("BaseUrl", out var baseUrlProp) ? baseUrlProp.GetString() : null;
            _logger.LogInformation("Verified appsettings.json VerificationUrl - BaseUrl: '{BaseUrl}'", 
                verifyBaseUrl ?? "null");
        }
    }
}
