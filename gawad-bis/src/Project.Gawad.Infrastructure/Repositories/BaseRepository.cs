using System.Linq.Expressions;
using Microsoft.EntityFrameworkCore;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects;

namespace Project.Gawad.Infrastructure.Repositories;

public abstract class BaseRepository<T> : IBaseRepository<T>
    where T : Entity
{
    protected readonly GawadMongoDbContext _dbContext;

    public BaseRepository(GawadMongoDbContext dbContext)
    {
        _dbContext = dbContext ?? throw new ArgumentNullException(nameof(dbContext));
        _dbContext.Database.AutoTransactionBehavior = AutoTransactionBehavior.Never;
    }

    public virtual async Task<T?> GetByIdAsync(ObjectId? id, bool isDeleted = false)
    {
        return await _dbContext.Set<T>()
            .AsNoTracking()
            .FirstOrDefaultAsync(x => x.Id == id && x.IsDeleted == isDeleted);
    }

    public virtual async Task<IReadOnlyList<T>> GetAllAsync(int skip = 0, int take = 0)
    {
        if (skip > 0 && take > 0)
            await _dbContext.Set<T>().Skip(skip).Take(take).ToListAsync();

        return await _dbContext.Set<T>().AsNoTracking().ToListAsync();
    }

    public virtual async Task<PaginatedRecords<T>> GetPaginatedRecordsAsync(int page, int itemsPerPage,
        bool sortAscending, Func<T, T> select,
        Expression<Func<T, object>>? order = null, Func<T, bool>? filter = null)
    {
        int totalRecords = 0, totalFilteredRecords = 0;

        List<T> data;

        var query = _dbContext.Set<T>().AsNoTracking().Where(t => !t.IsDeleted);

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

        return new PaginatedRecords<T>
        {
            PageNumber = page,
            PageSize = itemsPerPage,
            RecordsTotal = totalRecords,
            RecordsFiltered = totalFilteredRecords,
            Data = data
        };
    }

    public virtual async Task<T> AddAsync(T entity)
    {
        await _dbContext.Set<T>().AddAsync(entity);
        return entity;
    }

    public virtual async Task<T[]> AddRangeAsync(T[] entities)
    {
        await _dbContext.Set<T>().AddRangeAsync(entities);
        return entities;
    }

    public virtual async Task<IEnumerable<T>> AddRangeAsync(IEnumerable<T> entities)
    {
        await _dbContext.Set<T>().AddRangeAsync(entities);
        return entities;
    }


    public virtual Task<T> UpdateAsync(T entity)
    {
        _dbContext.Attach(entity);
        _dbContext.Entry(entity).State = EntityState.Modified;

        _dbContext.Set<T>().Update(entity);
        return Task.FromResult(entity);
    }

    public virtual async Task SaveChangesAsync()
    {
        await _dbContext.SaveChangesAsync();
        _dbContext.ChangeTracker.Clear();
    }
}