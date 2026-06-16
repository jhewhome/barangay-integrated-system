using MongoDB.Bson;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Integration;
using Project.Gawad.Domain.Objects.Resident;

namespace Project.Gawad.Core.Providers;

public interface IResidentsProvider
{
    Task<UpdateResidentObject?> GetCreateUpdateResidentObjectAsync(ObjectId residentId);

    Task<PaginatedRecords<ResidentListObject>> GetResidentsListAsync(int page = 1, int itemsPerPage = 10,
        int sortColIndex = 0, string sortColDir = "asc", string? search = null);

    Task<ResidentProfileObject?> GetResidentProfileObjectAsync(ObjectId residentId);

    Task<GawadResidentIntegrationDto?> GetResidentIntegrationExportAsync(ObjectId residentId);
}