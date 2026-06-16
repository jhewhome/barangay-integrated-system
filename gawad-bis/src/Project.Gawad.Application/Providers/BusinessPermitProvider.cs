using AutoMapper;
using MongoDB.Bson;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Domain.Objects.BusinessPermit;

namespace Project.Gawad.Application.Providers;

public class BusinessPermitProvider(
    IBusinessPermitRepository businessPermitRepository,
    IResidentsRepository residentsRepository,
    IPersonRepository personRepository,
    IMapper mapper) : IBusinessPermitProvider
{
    private readonly IBusinessPermitRepository _businessPermitRepository =
        businessPermitRepository ?? throw new ArgumentNullException(nameof(businessPermitRepository));
    
    private readonly IMapper _mapper =
        mapper ?? throw new ArgumentNullException(nameof(mapper));
    
    private readonly IPersonRepository _personRepository =
        personRepository ?? throw new ArgumentNullException(nameof(personRepository));
    
    private readonly IResidentsRepository _residentsRepository =
        residentsRepository ?? throw new ArgumentNullException(nameof(residentsRepository));
    
    public async Task<BusinessPermitFormObject?> CreateBusinessPermitFormObjectByResidentId(ObjectId residentId)
    {   
        var result = await _residentsRepository.GetByIdAsync(residentId);

        if (result is not null)
        {
            result!.Person = await _personRepository.GetByIdAsync(result.PersonId);
            return _mapper.Map<BusinessPermitFormObject>(result);
        }

        return null;
    }
}