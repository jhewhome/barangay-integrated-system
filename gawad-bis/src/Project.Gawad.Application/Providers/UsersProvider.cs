using System.Linq.Expressions;
using System.Security.Claims;
using AutoMapper;
using Microsoft.AspNetCore.Identity;
using Microsoft.Extensions.Caching.Memory;
using MongoDB.Bson;
using Project.Gawad.Core.Extensions;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Domain.Identity;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.UserManagement;
using Project.Gawad.Domain.Objects.Integration;

namespace Project.Gawad.Application.Providers;

public class UsersProvider(
    IUsersRepository usersRepository,
    UserManager<ApplicationUser> userManager,
    IMapper mapper,
    IMemoryCache memoryCache) : IUsersProvider
{
    private readonly IMapper _mapper =
        mapper ?? throw new ArgumentNullException(nameof(mapper));

    private readonly IMemoryCache _memoryCache =
        memoryCache ?? throw new ArgumentNullException(nameof(memoryCache));

    private readonly UserManager<ApplicationUser> _userManager =
        userManager ?? throw new ArgumentNullException(nameof(userManager));

    private readonly IUsersRepository _usersRepository =
        usersRepository ?? throw new ArgumentNullException(nameof(usersRepository));

    /// <inheritdoc/>
    public async Task<PaginatedRecords<AppUserListObject>> GetUsersListAsync(int page = 1, int itemsPerPage = 10,
        int sortColIndex = 0, string sortColDir = "asc", string? search = null)
    {
        var isAscending = sortColDir.Equals("asc", StringComparison.InvariantCultureIgnoreCase);

        Expression<Func<ApplicationUser, object>> order = sortColIndex switch
        {
            0 => a => a.UserName,
            1 => a => a.FirstName,
            _ => order = a => a.UserName
        };

        Func<ApplicationUser, bool>? filter = string.IsNullOrEmpty(search)
            ? null
            : new Func<ApplicationUser, bool>(a =>
                a.FirstName.ToLower().Contains(search.ToLower())
                || a.LastName.ToLower().Contains(search.ToLower())
                || a.UserName.ToLower().Contains(search.ToLower())
            );
        Func<ApplicationUser, ApplicationUser>? select = r => r;
        var paginateUsers = await _usersRepository.GetPaginatedRecordsAsync(page, itemsPerPage,
            isAscending, select, order, filter);

        return paginateUsers;
    }

    /// <inheritdoc/>
    public async Task<ApplicationUserObject?> GetCurrentUserAsync(ClaimsPrincipal principal)
    {
        var nameidentifier = principal.FindFirst(ClaimTypes.NameIdentifier)?.Value;

        if (string.IsNullOrEmpty(nameidentifier))
            throw new ArgumentNullException(nameof(nameidentifier));

        ApplicationUserObject? applicationUserObject = null;

        var cacheKey = $"user_{nameidentifier}";

        if (_memoryCache.TryGetValue(cacheKey, out applicationUserObject))
            return applicationUserObject;

        var applicationUser = await _userManager.FindByIdAsync(nameidentifier);
        applicationUserObject = _mapper.Map<ApplicationUser, ApplicationUserObject>(applicationUser);

        var cacheOptions = new MemoryCacheEntryOptions
        {
            AbsoluteExpirationRelativeToNow = TimeSpan.FromMinutes(60), // Cache expires in 60 minutes
            SlidingExpiration = TimeSpan.FromMinutes(40) // Refresh cache if accessed within 20 minutes
        };

        _memoryCache.Set(cacheKey, applicationUserObject, cacheOptions);

        return applicationUserObject;
    }

    /// <inheritdoc/>
    public async Task<ApplicationUserObject?> GetApplicationUserObjectByIdAsync(ObjectId userId)
    {
        ApplicationUserObject? applicationUserObject = null;

        var cacheKey = $"user_{userId.ToString()}";

        if (_memoryCache.TryGetValue(cacheKey, out applicationUserObject))
            return applicationUserObject;

        var applicationUser = await _userManager.FindByIdAsync(userId.ToString());
        applicationUserObject = _mapper.Map<ApplicationUser, ApplicationUserObject?>(applicationUser);

        _memoryCache.Set(cacheKey, applicationUserObject, new MemoryCacheEntryOptions
        {
            AbsoluteExpirationRelativeToNow = TimeSpan.FromMinutes(60), // Cache expires in 60 minutes
            SlidingExpiration = TimeSpan.FromMinutes(40) // Refresh cache if accessed within 20 minutes
        });

        return applicationUserObject;
    }

    /// <inheritdoc/>
    public async Task<UpdateUserObject?> GetUpdateUserObjectByIdAsync(string userId)
    {
        var applicationUser = await _userManager.FindByIdAsync(userId);
        if (applicationUser is null) return null;

        return _mapper.Map<ApplicationUser, UpdateUserObject?>(applicationUser);
    }

    /// <inheritdoc/>
    public async Task<IReadOnlyList<GawadStaffIntegrationDto>> GetStaffIntegrationExportAsync()
    {
        var users = await _usersRepository.GetActiveUsersAsync();

        return users.Select(u => new GawadStaffIntegrationDto
        {
            UserName = u.UserName!.Trim(),
            FirstName = u.FirstName,
            LastName = u.LastName,
            FullName = $"{u.FirstName} {u.LastName}".Trim(),
            GawadRole = u.RoleType.GetEnumDisplayName(),
            GawadRoleType = u.RoleType.ToString(),
        }).ToList();
    }
}