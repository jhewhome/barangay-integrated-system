using AutoMapper;
using Microsoft.Extensions.Logging;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.Clearance;

namespace Project.Gawad.Application.Services;

public class ClearanceService(
    IClearanceRepository clearanceRepository,
    IBarangayTransactionRepository barangayTransactionRepository,
    IPersonRepository personRepository,
    IResidentsRepository residentsRepository,
    IMapper mapper,
    ILogger<ClearanceService> logger) : IClearanceService
{
    private readonly IBarangayTransactionRepository _barangayTransactionRepository =
        barangayTransactionRepository ?? throw new ArgumentNullException(nameof(barangayTransactionRepository));

    private readonly IClearanceRepository _clearanceRepository =
        clearanceRepository ?? throw new ArgumentNullException(nameof(clearanceRepository));

    private readonly ILogger<ClearanceService> _logger =
        logger ?? throw new ArgumentNullException(nameof(logger));

    private readonly IMapper _mapper =
        mapper ?? throw new ArgumentNullException(nameof(mapper));

    private readonly IPersonRepository _personRepository =
        personRepository ?? throw new ArgumentNullException(nameof(personRepository));

    private readonly IResidentsRepository _residentsRepository =
        residentsRepository ?? throw new ArgumentNullException(nameof(residentsRepository));

    public async Task<ServiceResponse<ClearanceFormObject>> ApplyClearanceApplication(
        ClearanceFormObject clearanceFormObject,
        ApplicationUserObject createdBy)
    {
        var clearance = new Clearance
        {
            BarangayTrasaction = new BarangayTrasaction()
        };

        clearance = _mapper.Map<ClearanceFormObject, Clearance>(clearanceFormObject);

        var existingPersonRecord = await _personRepository.GetPersonByNameAndBirthdate(
            clearanceFormObject.FirstName, clearanceFormObject.LastName,
            clearanceFormObject.DateOfBirth!.Value);

        // do not insert Person, reference the existing ObjectId instead
        if (existingPersonRecord is not null)
        {
            clearance.PersonId = existingPersonRecord.Id!.Value;
            clearance.Person = null;
            clearance.BarangayTrasaction.PersonId = existingPersonRecord.Id!.Value;

            var resident = await _residentsRepository.GetResidentByPersonIdAsync(existingPersonRecord.Id!.Value);
            clearance.BarangayTrasaction.ResidentId = resident?.Id;
        }
        else
        {
            clearance.Person.CreatedById = createdBy.Id;
            clearance.BarangayTrasaction.PersonId = clearance.Person.Id.Value;
        }

        clearance.BarangayTrasaction.ControlNumber = await _barangayTransactionRepository.GenerateControlNumber();

        clearance.BarangayTrasaction.CreatedById = createdBy.Id;
        clearance.CreatedById = createdBy.Id;

        await _clearanceRepository.AddAsync(clearance);
        await _clearanceRepository.SaveChangesAsync();

        clearanceFormObject.Id = clearance.Id;
        clearanceFormObject.TransactionId = clearance.BarangayTrasaction.Id;

        return new ServiceResponse<ClearanceFormObject>(clearanceFormObject);
    }
}