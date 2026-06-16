using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.Clearance;

namespace Project.Gawad.Core.Services;

public interface IClearanceService
{
    Task<ServiceResponse<ClearanceFormObject>> ApplyClearanceApplication(ClearanceFormObject clearanceFormObject,
        ApplicationUserObject createdBy);
}