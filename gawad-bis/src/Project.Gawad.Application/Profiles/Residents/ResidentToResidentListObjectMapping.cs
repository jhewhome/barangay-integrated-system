using AutoMapper;
using Project.Gawad.Application.Utils;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums;
using Project.Gawad.Domain.Objects.Resident;

namespace Project.Gawad.Application.Profiles.Residents;

public class ResidentToResidentListObjectMapping : Profile
{
    public ResidentToResidentListObjectMapping()
    {
        CreateMap<Resident, ResidentListObject>()
            .ForMember(dst => dst.Id,
                src => src.MapFrom(x => x.Id.ToString()))
            .ForMember(dst => dst.Name,
                src => src.MapFrom(x => $"{x.Person.LastName}, {x.Person.FirstName} {x.Person.MiddleName}"))
            .ForMember(dst => dst.Gender,
                src => src.MapFrom(x => x.Person.Gender.ToString()))
            .ForMember(dst => dst.Address,
                src => src.MapFrom(x => AddressHelper.GetAddress(x, AddressType.Current, true)))
            .ForMember(dst => dst.CivilStatus,
                src => src.MapFrom(x => x.Person.CivilStatus.ToString()))
            .ForMember(dst => dst.PersonId,
                src => src.MapFrom(x => x.PersonId.ToString()));
    }
}