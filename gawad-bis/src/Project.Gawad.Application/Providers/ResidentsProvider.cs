using System.Linq.Expressions;
using AutoMapper;
using MongoDB.Bson;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Integration;
using Project.Gawad.Domain.Objects.Resident;

namespace Project.Gawad.Application.Providers;

public class ResidentsProvider(
    IResidentsRepository residentsRepository,
    IPersonRepository personRepository,
    IMapper mapper) : IResidentsProvider
{
    private readonly IMapper _mapper = 
        mapper ?? throw new ArgumentNullException(nameof(mapper));

    private readonly IPersonRepository _personRepository =
        personRepository ?? throw new ArgumentNullException(nameof(personRepository));

    private readonly IResidentsRepository _residentsRepository =
        residentsRepository ?? throw new ArgumentNullException(nameof(residentsRepository));

    public async Task<UpdateResidentObject?> GetCreateUpdateResidentObjectAsync(ObjectId residentId)
    {
        var result = await _residentsRepository.GetByIdAsync(residentId);

        if (result is not null)
        {
            result.Person = await _personRepository.GetByIdAsync(result.PersonId);
            return _mapper.Map<Resident, UpdateResidentObject>(result);
        }

        return null;
    }

    public async Task<PaginatedRecords<ResidentListObject>> GetResidentsListAsync(int page = 1, int itemsPerPage = 10,
        int sortColIndex = 0, string sortColDir = "asc", string? search = null)
    {
        var isAscending = sortColDir.Equals("asc", StringComparison.InvariantCultureIgnoreCase);

        Expression<Func<Person, object>> order = sortColIndex switch
        {
            0 => a => a.LastName,
            1 => a => a.LastName,
            2 => a => a.LastName,
            3 => a => a.LastName,
            4 => a => a.Gender,
            5 => a => a.CivilStatus,
            _ => order = a => a.CreatedDate
        };

        Func<Person, bool>? filter = string.IsNullOrEmpty(search)
            ? new Func<Person, bool>(a => a.IsResident)
            : new Func<Person, bool>(a =>
                (a.FirstName.ToLower().Contains(search?.ToLower())
                 || (string.IsNullOrEmpty(a.MiddleName) ? false : a.MiddleName.ToLower().Contains(search?.ToLower()))
                 || a.LastName.ToLower().Contains(search?.ToLower())) &&
                a.IsResident
            );

        Func<Person, Person>? select = r => r;

        var paginatedResult = await _personRepository.GetResidentPersonsPaginatedRecordsAsync(page, itemsPerPage,
            isAscending, select, order, filter);

        var residents = new List<Resident>();
        foreach (var person in paginatedResult.Data)
        {
            var resident = await _residentsRepository.GetResidentByPersonIdAsync(person.Id.Value);
            if (resident is not null)
            {
                resident.Person = person;
                residents.Add(resident);
            }
        }

        return new PaginatedRecords<ResidentListObject>
        {
            PageNumber = page,
            PageSize = itemsPerPage,
            RecordsTotal = paginatedResult.RecordsTotal,
            RecordsFiltered = paginatedResult.RecordsFiltered,
            Data = residents.Select(a => _mapper.Map<ResidentListObject>(a)).ToList()
        };
    }

    public async Task<ResidentProfileObject?> GetResidentProfileObjectAsync(ObjectId residentId)
    {
        var result = await _residentsRepository.GetByIdAsync(residentId);

        if (result is not null)
        {
            result!.Person = await _personRepository.GetByIdAsync(result.PersonId);
            return _mapper.Map<ResidentProfileObject>(result);
        }

        return null;
    }

    public async Task<GawadResidentIntegrationDto?> GetResidentIntegrationExportAsync(ObjectId residentId)
    {
        var result = await _residentsRepository.GetByIdAsync(residentId);
        if (result is null)
        {
            return null;
        }

        var person = await _personRepository.GetByIdAsync(result.PersonId);
        if (person is null)
        {
            return null;
        }

        var updateObject = await GetCreateUpdateResidentObjectAsync(residentId);
        if (updateObject is null)
        {
            return null;
        }

        var current = updateObject.GetCurrentAddress();
        var addressParts = new[]
        {
            current.AddressLine1,
            current.AddressLine2,
            current.Barangay,
            current.City,
            current.Province
        }.Where(static part => !string.IsNullOrWhiteSpace(part));

        return new GawadResidentIntegrationDto
        {
            Id = result.Id.ToString(),
            FirstName = person.FirstName,
            MiddleName = person.MiddleName,
            LastName = person.LastName,
            Suffix = string.IsNullOrWhiteSpace(person.Suffix) ? null : person.Suffix,
            FullName = string.Join(' ', new[] { person.FirstName, person.MiddleName, person.LastName, person.Suffix }
                .Where(static part => !string.IsNullOrWhiteSpace(part))),
            Sex = person.Gender == Gender.Female ? "F" : "M",
            Birthdate = person.DateOfBirth.ToLocalTime().ToString("yyyy-MM-dd"),
            ContactNumber = null,
            Address = string.Join(", ", addressParts),
            Barangay = string.IsNullOrWhiteSpace(current.Barangay) ? "Balong Bato" : current.Barangay,
            CivilStatus = MapCivilStatus(person.CivilStatus),
            IsBarangayResident = person.IsResident
        };
    }

    private static string? MapCivilStatus(CivilStatus status)
    {
        return status switch
        {
            CivilStatus.Married => "married",
            CivilStatus.Widowed => "widowed",
            CivilStatus.Separated => "separated",
            CivilStatus.Divorced => "separated",
            _ => "single"
        };
    }
}