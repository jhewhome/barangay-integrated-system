using System.Linq.Expressions;
using MongoDB.Bson;
using Project.Gawad.Domain.Objects;

namespace Project.Gawad.Core.Repositories;

public interface IBaseRepository<T> where T : class
{
    Task<T?> GetByIdAsync(ObjectId? id, bool isDeleted = false);

    Task<IReadOnlyList<T>> GetAllAsync(int skip = 0, int take = 0);

    Task<PaginatedRecords<T>> GetPaginatedRecordsAsync(int page, int itemPerPage, bool sortAscending,
        Func<T, T> select, Expression<Func<T, object>>? order = null, Func<T, bool>? filter = null);

    Task<T> AddAsync(T entity);

    Task<T[]> AddRangeAsync(T[] entities);

    Task<IEnumerable<T>> AddRangeAsync(IEnumerable<T> entities);

    Task<T> UpdateAsync(T entity);

    Task SaveChangesAsync();
}