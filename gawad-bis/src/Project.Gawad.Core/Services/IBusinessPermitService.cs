using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.BusinessPermit;

namespace Project.Gawad.Core.Services;

public interface IBusinessPermitService
{
    Task<ServiceResponse<BusinessPermitFormObject>> ApplyBusinessPermit(
        BusinessPermitFormObject businessPermitFormObject,
        ApplicationUserObject createdBy);
}