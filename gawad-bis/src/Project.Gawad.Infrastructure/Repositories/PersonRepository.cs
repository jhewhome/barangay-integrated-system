using System.Linq.Expressions;
using Microsoft.EntityFrameworkCore;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects;

namespace Project.Gawad.Infrastructure.Repositories;

public class PersonRepository(GawadMongoDbContext dbContext) : BaseRepository<Person>(dbContext), IPersonRepository
{
    public async Task<Person?> GetPersonByNameAndBirthdate(string firstName, string lastName, DateTime birthDate)
    {
        return await _dbContext.Persons.AsQueryable().AsNoTracking().Where(p =>
            p.LastName.ToLower().Equals(lastName.ToLower(), StringComparison.OrdinalIgnoreCase) &&
            p.FirstName.ToLower().Equals(firstName.ToLower(), StringComparison.OrdinalIgnoreCase) &&
            p.DateOfBirth.Date >= birthDate.Date && p.DateOfBirth.Date < birthDate.Date.AddDays(1)
        ).FirstOrDefaultAsync();
    }

    public async Task<PaginatedRecords<Person>> GetResidentPersonsPaginatedRecordsAsync(int page, int itemsPerPage,
        bool sortAscending, Func<Person, Person> select,
        Expression<Func<Person, object>>? order = null, Func<Person, bool>? filter = null)
    {
        int totalRecords = 0, totalFilteredRecords = 0;

        List<Person> data;

        var query = _dbContext.Persons.AsNoTracking().Where(t => !t.IsDeleted && t.IsResident);

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

        return new PaginatedRecords<Person>
        {
            PageNumber = page,
            PageSize = itemsPerPage,
            RecordsTotal = totalRecords,
            RecordsFiltered = totalFilteredRecords,
            Data = data
        };
    }
}