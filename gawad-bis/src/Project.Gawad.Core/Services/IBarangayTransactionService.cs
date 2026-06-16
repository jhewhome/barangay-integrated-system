using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;

namespace Project.Gawad.Core.Services;

public interface IBarangayTransactionService
{
    Task<ServiceResponse<BarangayTrasaction>> DeleteTransactionAsync(string id, ApplicationUserObject deleteBy);
}