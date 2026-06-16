using AutoMapper;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Application.Providers;

public class PersonProvider(IPersonRepository personRepository, IMapper mapper) : IPersonProvider
{
    private readonly IMapper _mapper
        = mapper ?? throw new ArgumentNullException(nameof(mapper));

    private readonly IPersonRepository _personRepository =
        personRepository ?? throw new ArgumentNullException(nameof(personRepository));

    public Task<Person?> GetPersonByNameAndBirthdate(string firstName, string lastName, DateTime birthDate)
    {
        return _personRepository.GetPersonByNameAndBirthdate(firstName, lastName, birthDate);
    }
}