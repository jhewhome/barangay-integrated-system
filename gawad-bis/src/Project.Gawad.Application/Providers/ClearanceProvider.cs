using AutoMapper;
using MongoDB.Bson;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Domain.Objects.Clearance;

namespace Project.Gawad.Application.Providers;

public class ClearanceProvider(IClearanceRepository clearanceRepository, 
    IResidentsRepository residentsRepository,
    IPersonRepository personRepository,
    IMapper mapper) : IClearanceProvider
{
    private readonly IClearanceRepository _clearanceRepository =
        clearanceRepository ?? throw new ArgumentNullException(nameof(clearanceRepository));

    private readonly IMapper _mapper =
        mapper ?? throw new ArgumentNullException(nameof(mapper));
    
    private readonly IPersonRepository _personRepository =
        personRepository ?? throw new ArgumentNullException(nameof(personRepository));
    
    private readonly IResidentsRepository _residentsRepository =
        residentsRepository ?? throw new ArgumentNullException(nameof(residentsRepository));

    public async Task<ClearanceFormObject?> CreateClearanceFormObjectByResidentId(ObjectId residentId)
    {   
        var result = await _residentsRepository.GetByIdAsync(residentId);

        if (result is not null)
        {
            result!.Person = await _personRepository.GetByIdAsync(result.PersonId);
            return _mapper.Map<ClearanceFormObject>(result);
        }

        return null;
    }

}