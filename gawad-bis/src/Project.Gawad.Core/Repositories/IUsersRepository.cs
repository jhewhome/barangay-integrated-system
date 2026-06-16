using System.Linq.Expressions;
using MongoDB.Bson;
using Project.Gawad.Domain.Identity;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.UserManagement;

namespace Project.Gawad.Core.Repositories;

public interface IUsersRepository
{
    Task<PaginatedRecords<AppUserListObject>> GetPaginatedRecordsAsync(int page, int itemsPerPage, bool sortAscending,
        Func<ApplicationUser, ApplicationUser> select, Expression<Func<ApplicationUser, object>>? order = null,
        Func<ApplicationUser, bool>? filter = null);

    Task<ApplicationUser> AddUserAsync(ApplicationUser applicationUser);

    Task<ApplicationUser> UpdateUserAsync(ApplicationUser applicationUser);

    Task<bool> DeleteUserAsync(ObjectId applicationUserId);

    Task SaveChangesAsync();

    Task<List<ApplicationUser>> GetActiveUsersAsync();
}