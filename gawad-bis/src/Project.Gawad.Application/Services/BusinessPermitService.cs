using AutoMapper;
using Microsoft.Extensions.Logging;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.BusinessPermit;

namespace Project.Gawad.Application.Services;

public class BusinessPermitService(
    IBusinessPermitRepository businessPermitRepository,
    IBarangayTransactionRepository barangayTransactionRepository,
    IPersonRepository personRepository,
    IResidentsRepository residentsRepository,
    IMapper mapper,
    ILogger<BusinessPermitService> logger) : IBusinessPermitService
{
    private readonly IBarangayTransactionRepository _barangayTransactionRepository =
        barangayTransactionRepository ?? throw new ArgumentNullException(nameof(barangayTransactionRepository));

    private readonly IBusinessPermitRepository _businessPermitRepository =
        businessPermitRepository ?? throw new ArgumentNullException(nameof(businessPermitRepository));

    private readonly ILogger<BusinessPermitService> _logger =
        logger ?? throw new ArgumentNullException(nameof(logger));

    private readonly IMapper _mapper =
        mapper ?? throw new ArgumentNullException(nameof(mapper));

    private readonly IPersonRepository _personRepository =
        personRepository ?? throw new ArgumentNullException(nameof(personRepository));

    private readonly IResidentsRepository _residentsRepository =
        residentsRepository ?? throw new ArgumentNullException(nameof(residentsRepository));

    public async Task<ServiceResponse<BusinessPermitFormObject>> ApplyBusinessPermit(
        BusinessPermitFormObject businessPermitFormObject,
        ApplicationUserObject createdBy)
    {
        var businessPermit = new BusinessPermit
        {
            BarangayTrasaction = new BarangayTrasaction()
        };

        businessPermit = _mapper.Map<BusinessPermitFormObject, BusinessPermit>(businessPermitFormObject);

        var existingPersonRecord = await _personRepository.GetPersonByNameAndBirthdate(
            businessPermitFormObject.FirstName, businessPermitFormObject.LastName,
            businessPermitFormObject.DateOfBirth!.Value);

        // do not insert Person, reference the existing ObjectId instead
        if (existingPersonRecord is not null)
        {
            businessPermit.PersonId = existingPersonRecord.Id!.Value;
            businessPermit.Person = null;
            businessPermit.BarangayTrasaction.PersonId = existingPersonRecord.Id!.Value;

            var resident = await _residentsRepository.GetResidentByPersonIdAsync(existingPersonRecord.Id!.Value);
            businessPermit.BarangayTrasaction.ResidentId = resident?.Id;
        }
        else
        {
            businessPermit.Person.CreatedById = createdBy.Id;
            businessPermit.BarangayTrasaction.PersonId = businessPermit.Person.Id.Value;
        }

        businessPermit.BarangayTrasaction.ControlNumber = await _barangayTransactionRepository.GenerateControlNumber();

        businessPermit.BarangayTrasaction.CreatedById = createdBy.Id;
        businessPermit.CreatedById = createdBy.Id;

        await _businessPermitRepository.AddAsync(businessPermit);
        await _businessPermitRepository.SaveChangesAsync();

        businessPermitFormObject.Id = businessPermit.Id;
        businessPermitFormObject.TransactionId = businessPermit.BarangayTrasaction.Id;

        return new ServiceResponse<BusinessPermitFormObject>(businessPermitFormObject);
    }
}