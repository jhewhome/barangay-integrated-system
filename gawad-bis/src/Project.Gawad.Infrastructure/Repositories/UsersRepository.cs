using System.Linq.Expressions;
using AutoMapper;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Identity;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.UserManagement;

namespace Project.Gawad.Infrastructure.Repositories;

public class UsersRepository : IUsersRepository
{
    private readonly GawadIdentityMongoDbContext _dbContext;

    private readonly ILogger<UsersRepository> _logger;
    private readonly IMapper _mapper;

    public UsersRepository(GawadIdentityMongoDbContext dbContext,
        IMapper mapper,
        ILogger<UsersRepository> logger)
    {
        _mapper = mapper ?? throw new ArgumentNullException(nameof(mapper));
        _logger = logger ?? throw new ArgumentNullException(nameof(logger));
        _dbContext = dbContext ?? throw new ArgumentNullException(nameof(dbContext));
        _dbContext.Database.AutoTransactionBehavior = AutoTransactionBehavior.Never;
    }

    public async Task<PaginatedRecords<AppUserListObject>> GetPaginatedRecordsAsync(int page, int itemsPerPage,
        bool sortAscending, Func<ApplicationUser, ApplicationUser> select,
        Expression<Func<ApplicationUser, object>>? order = null, Func<ApplicationUser, bool>? filter = null)
    {
        int totalRecords = 0, totalFilteredRecords = 0;

        List<ApplicationUser> data;

        var query = _dbContext.ApplicationUsers.AsNoTracking().Where(x => !x.IsDeleted);

        totalRecords = await query.CountAsync();

        if (order is not null)
            query = sortAscending ? query.OrderBy(order) : query.OrderByDescending(order);

        if (filter is not null)
        {
            totalFilteredRecords = query.Count(filter);

            query = query.Where(filter)
                .AsQueryable();
        }
        else
        {
            totalFilteredRecords = totalRecords;
        }

        query = query.Skip((page - 1) * itemsPerPage)
            .Take(itemsPerPage);

        data = query
            .Select(select)
            .AsQueryable()
            .ToList();

        return new PaginatedRecords<AppUserListObject>
        {
            PageNumber = page,
            PageSize = itemsPerPage,
            RecordsTotal = totalRecords,
            RecordsFiltered = totalFilteredRecords,
            Data = data.Select(a => _mapper.Map<AppUserListObject>(a)).ToList()
        };
    }

    public async Task<ApplicationUser> AddUserAsync(ApplicationUser applicationUser)
    {
        await _dbContext.ApplicationUsers.AddAsync(applicationUser);
        return applicationUser;
    }

    public async Task<ApplicationUser> UpdateUserAsync(ApplicationUser applicationUser)
    {
        _dbContext.Attach(applicationUser);
        _dbContext.Entry(applicationUser).State = EntityState.Modified;

        _dbContext.ApplicationUsers.Update(applicationUser);
        return applicationUser;
    }

    public async Task<bool> DeleteUserAsync(ObjectId applicationUserId)
    {
        try
        {
            var applicationUser = _dbContext.ApplicationUsers.FirstOrDefault(x => x.Id == applicationUserId);

            applicationUser.IsDeleted = true;

            await UpdateUserAsync(applicationUser);

            await SaveChangesAsync();
        }
        catch (Exception e)
        {
            _logger.LogError(e.Message);
            return false;
        }

        return true;
    }

    public async Task SaveChangesAsync()
    {
        await _dbContext.SaveChangesAsync();
        _dbContext.ChangeTracker.Clear();
    }

    public async Task<List<ApplicationUser>> GetActiveUsersAsync()
    {
        return await _dbContext.ApplicationUsers.AsNoTracking()
            .Where(x => !x.IsDeleted && x.UserName != null && x.UserName != "")
            .OrderBy(x => x.UserName)
            .ToListAsync();
    }
}