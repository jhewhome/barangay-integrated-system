using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Core.Providers;

public interface IPersonProvider
{
    Task<Person?> GetPersonByNameAndBirthdate(string firstName, string lastName, DateTime birthDate);
}