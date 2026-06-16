using Project.Gawad.Domain.Enums;

namespace Project.Gawad.Core.Services;

public interface IDocumentService
{
    Task<byte[]> GenerateDocument(string id, BarangayDocumentType barangayDocumentType,
        IDictionary<string, string> data, CancellationToken cancellationToken = default);
}