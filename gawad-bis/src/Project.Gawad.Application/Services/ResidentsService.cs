using AutoMapper;
using Microsoft.Extensions.Logging;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.Resident;

namespace Project.Gawad.Application.Services;

public class ResidentsService(
    IResidentsRepository residentsRepository,
    IPersonRepository personRepository,
    IMapper mapper,
    ILogger<ResidentsService> logger) : IResidentsService
{
    private readonly ILogger<ResidentsService> _logger =
        logger ?? throw new ArgumentNullException(nameof(logger));

    private readonly IMapper _mapper =
        mapper ?? throw new ArgumentNullException(nameof(mapper));

    private readonly IPersonRepository _personRepository =
        personRepository ?? throw new ArgumentNullException(nameof(personRepository));

    private readonly IResidentsRepository _residentsRepository =
        residentsRepository ?? throw new ArgumentNullException(nameof(residentsRepository));

    /// <inheritdoc/>
    public async Task<ServiceResponse<CreateResidentObject>> CreateResident(CreateResidentObject createResidentObject,
        ApplicationUserObject createdBy)
    {
        var resident = new Resident
        {
            Id = ObjectId.GenerateNewId(),
            Person = new Person(),
            Addresses = new List<Address>
            {
                new() { Type = AddressType.Permanent }, new() { Type = AddressType.Current }
            }
        };

        resident = _mapper.Map<CreateResidentObject, Resident>(createResidentObject);

        resident.CreatedById = createdBy.Id;

        resident.Person.CreatedById = createdBy.Id;

        await _residentsRepository.AddAsync(resident);
        await _residentsRepository.SaveChangesAsync();

        createResidentObject.Id = resident.Id;

        return new ServiceResponse<CreateResidentObject>(createResidentObject);
    }

    /// <inheritdoc/>
    public async Task<ServiceResponse<UpdateResidentObject>> UpdateResident(UpdateResidentObject updateResidentObject,
        ApplicationUserObject updatedBy)
    {
        var resident = await _residentsRepository.GetByIdAsync(updateResidentObject.Id!.Value);

        if (resident is null)
            throw new ApplicationException($"Resident with Id {updateResidentObject.Id} does not exist.");

        var person = await _personRepository.GetByIdAsync(resident.PersonId!.Value);

        resident = _mapper.Map(updateResidentObject, resident);

        resident.LastModifiedById = updatedBy.Id;

        person = _mapper.Map(updateResidentObject, person);

        person.LastModifiedById = updatedBy.Id;

        await _personRepository.UpdateAsync(person!);
        await _personRepository.SaveChangesAsync();

        await _residentsRepository.UpdateAsync(resident);
        await _residentsRepository.SaveChangesAsync();

        return new ServiceResponse<UpdateResidentObject>(updateResidentObject);
    }
    
    /// <inheritdoc/>
    public async Task<bool> RemoveResident(string id, ApplicationUserObject deletedBy)
    {
        if (string.IsNullOrEmpty(id))
            throw new ArgumentNullException(nameof(id));

        if (!ObjectId.TryParse(id, out var objectId))
            throw new ApplicationException($"Invalid id: {id}");

        var resident = await _residentsRepository.GetByIdAsync(objectId);

        if (resident is null)
            throw new ApplicationException($"Resident with Id {id} does not exist.");

        var person = await _personRepository.GetByIdAsync(resident.PersonId!.Value);

        resident.LastModifiedById = deletedBy.Id;
        resident.LastModifiedDate = DateTime.Now;
        resident.IsDeleted = true;

        person.LastModifiedById = deletedBy.Id;
        person.LastModifiedDate = DateTime.Now;
        person.IsDeleted = true;

        await _personRepository.UpdateAsync(person!);
        await _personRepository.SaveChangesAsync();

        await _residentsRepository.UpdateAsync(resident);
        await _residentsRepository.SaveChangesAsync();

        return true;
    }
    
    
}