using MongoDB.Bson;
using Project.Gawad.Domain.Objects.BusinessPermit;

namespace Project.Gawad.Core.Providers;

public interface IBusinessPermitProvider
{
    Task<BusinessPermitFormObject?> CreateBusinessPermitFormObjectByResidentId(ObjectId residentId);
}