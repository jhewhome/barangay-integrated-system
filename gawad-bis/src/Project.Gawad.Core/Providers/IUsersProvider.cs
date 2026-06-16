using System.Security.Claims;
using MongoDB.Bson;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.UserManagement;
using Project.Gawad.Domain.Objects.Integration;

namespace Project.Gawad.Core.Providers;

public interface IUsersProvider
{
    /// <summary>
    /// Get the paginated list of system users
    /// </summary>
    /// <param name="page">Current page number</param>
    /// <param name="itemsPerPage">Number of records per pag</param>
    /// <param name="sortColIndex">Index of sort column</param>
    /// <param name="sortColDir">Specific order if 'asc' (ascending) or 'desc' (descending)</param>
    /// <param name="search">Search keyword to match</param>
    /// <returns></returns>
    public Task<PaginatedRecords<AppUserListObject>> GetUsersListAsync(int page = 1
        , int itemsPerPage = 10, int sortColIndex = 0,
        string sortColDir = "asc", string? search = null);

    /// <summary>
    /// Return the ApplicationUserObject of the user based on the identifier in the claims
    /// </summary>
    /// <param name="principal"></param>
    /// <returns></returns>
    Task<ApplicationUserObject?> GetCurrentUserAsync(ClaimsPrincipal principal);

    /// <summary>
    /// Return the ApplicationUserObject of the user based on specified user id
    /// </summary>
    /// <param name="userId"></param>
    /// <returns></returns>
    Task<ApplicationUserObject?> GetApplicationUserObjectByIdAsync(ObjectId userId);

    /// <summary>
    /// Get the Update user object
    /// </summary>
    /// <param name="userId"></param>
    /// <returns></returns>
    Task<UpdateUserObject?> GetUpdateUserObjectByIdAsync(string userId);

    /// <summary>
    /// Active Gawad users for BHC staff account sync.
    /// </summary>
    Task<IReadOnlyList<GawadStaffIntegrationDto>> GetStaffIntegrationExportAsync();
}