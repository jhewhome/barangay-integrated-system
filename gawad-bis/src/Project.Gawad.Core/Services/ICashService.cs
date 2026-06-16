using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.Cash;

namespace Project.Gawad.Core.Services;

public interface ICashService
{
    Task<ServiceResponse<CreateCashSessionObject>> OpenCashSession(CreateCashSessionObject createSessionObject, ApplicationUserObject openedBy);

    Task<ServiceResponse<CloseCashSessionObject>> CloseCashSession(CloseCashSessionObject closeSessionObject, ApplicationUserObject closedBy);

    Task<bool> AddCashMovement(string sessionId, decimal amount, string movementType, string? reason, ApplicationUserObject createdBy);
}



