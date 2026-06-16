using AutoMapper;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Dashboard;

namespace Project.Gawad.Application.Profiles.Residents;

public class ResidentToBirthdayResidentListObjectMapping : Profile
{
    public ResidentToBirthdayResidentListObjectMapping()
    {
        CreateMap<Resident, BirthdayResidentListObject>()
            .ForMember(dst => dst.Id, src => src.MapFrom(s => s.Id.ToString()))
            .ForMember(dst => dst.FullName, src => src.MapFrom(s => 
                s.Person != null ? $"{s.Person.FirstName} {s.Person.LastName}".Trim() : ""))
            .ForMember(dst => dst.DateOfBirth, src => src.MapFrom(s => s.Person != null ? s.Person.DateOfBirth : DateTime.MinValue))
            .ForMember(dst => dst.Age, src => src.MapFrom(s => 
                s.Person != null ? DateTime.Today.Year - s.Person.DateOfBirth.Year - 
                (DateTime.Today.DayOfYear < s.Person.DateOfBirth.DayOfYear ? 1 : 0) : 0));
    }
}
