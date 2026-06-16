using System.Linq.Expressions;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects;

namespace Project.Gawad.Core.Repositories;

public interface IBarangayTransactionRepository : IBaseRepository<BarangayTrasaction>
{
    Task<string> GenerateControlNumber();

    Task<int> GetRecentTransactionCountAsync(int? fromRecentDays = null);

    Task<PaginatedRecords<BarangayTrasaction>> GetResidentTransactionPaginatedRecordsAsync(string residentId, int page,
        int itemsPerPage, bool sortAscending,
        Func<BarangayTrasaction, BarangayTrasaction> select, Expression<Func<BarangayTrasaction, object>>? order = null,
        Func<BarangayTrasaction, bool>? filter = null);

    Task<PaginatedRecords<BarangayTrasaction>> GetMostRecentTransactionPaginatedRecordsAsync(int lastNDays, int page,
        int itemsPerPage,
        bool sortAscending, Func<BarangayTrasaction, BarangayTrasaction> select,
        Expression<Func<BarangayTrasaction, object>>? order = null, Func<BarangayTrasaction, bool>? filter = null);
}