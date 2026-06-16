using System.Linq.Expressions;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects;

namespace Project.Gawad.Core.Repositories;

public interface IPersonRepository : IBaseRepository<Person>
{
    Task<Person?> GetPersonByNameAndBirthdate(string firstName, string lastName, DateTime birthDate);

    Task<PaginatedRecords<Person>> GetResidentPersonsPaginatedRecordsAsync(int page, int itemsPerPage,
        bool sortAscending, Func<Person, Person> select,
        Expression<Func<Person, object>>? order = null, Func<Person, bool>? filter = null);
}