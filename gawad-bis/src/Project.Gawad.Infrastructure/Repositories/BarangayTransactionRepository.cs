using System.Linq.Expressions;
using Microsoft.EntityFrameworkCore;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects;

namespace Project.Gawad.Infrastructure.Repositories;

public class BarangayTransactionRepository(GawadMongoDbContext dbContext)
    : BaseRepository<BarangayTrasaction>(dbContext), IBarangayTransactionRepository
{
    public async Task<string> GenerateControlNumber()
    {
        var maxNumber = await _dbContext.BarangayTrasactions.CountAsync();
        return $"{DateTime.Now.Year}-{maxNumber + 1:D5}";
    }

    public Task<int> GetRecentTransactionCountAsync(int? fromRecentDays = null)
    {
        if (fromRecentDays.HasValue)
            return _dbContext.BarangayTrasactions
                .Where(t => !t.IsDeleted && t.CreatedDate.Date >= DateTime.Now.Date.AddDays(fromRecentDays.Value * -1)
                                         && t.CreatedDate.Date <= DateTime.Now.Date.AddDays(1))
                .CountAsync();

        return _dbContext.BarangayTrasactions.AsQueryable().CountAsync();
    }

    public async Task<PaginatedRecords<BarangayTrasaction>> GetResidentTransactionPaginatedRecordsAsync(
        string residentId, int page, int itemsPerPage,
        bool sortAscending, Func<BarangayTrasaction, BarangayTrasaction> select,
        Expression<Func<BarangayTrasaction, object>>? order = null, Func<BarangayTrasaction, bool>? filter = null)
    {
        int totalRecords = 0, totalFilteredRecords = 0;

        List<BarangayTrasaction> data;

        var query = _dbContext.BarangayTrasactions.AsQueryable().AsNoTracking()
            .Where(t => !t.IsDeleted && t.ResidentId == ObjectId.Parse(residentId));

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

        return new PaginatedRecords<BarangayTrasaction>
        {
            PageNumber = page,
            PageSize = itemsPerPage,
            RecordsTotal = totalRecords,
            RecordsFiltered = totalFilteredRecords,
            Data = data
        };
    }

    public async Task<PaginatedRecords<BarangayTrasaction>> GetMostRecentTransactionPaginatedRecordsAsync(int lastNDays,
        int page, int itemsPerPage,
        bool sortAscending, Func<BarangayTrasaction, BarangayTrasaction> select,
        Expression<Func<BarangayTrasaction, object>>? order = null, Func<BarangayTrasaction, bool>? filter = null)
    {
        int totalRecords = 0, totalFilteredRecords = 0;

        List<BarangayTrasaction> data;

        var query = _dbContext.BarangayTrasactions.AsQueryable().AsNoTracking()
            .Where(t => !t.IsDeleted
                        && t.CreatedDate.Date >= DateTime.Now.Date.AddDays(lastNDays * -1)
                        && t.CreatedDate.Date <= DateTime.Now.Date.AddDays(1));

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

        return new PaginatedRecords<BarangayTrasaction>
        {
            PageNumber = page,
            PageSize = itemsPerPage,
            RecordsTotal = totalRecords,
            RecordsFiltered = totalFilteredRecords,
            Data = data
        };
    }
}