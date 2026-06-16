using MongoDB.Bson;
using Project.Gawad.Domain.Objects.Clearance;

namespace Project.Gawad.Core.Providers;

public interface IClearanceProvider
{
    Task<ClearanceFormObject?> CreateClearanceFormObjectByResidentId(ObjectId residentId);
}