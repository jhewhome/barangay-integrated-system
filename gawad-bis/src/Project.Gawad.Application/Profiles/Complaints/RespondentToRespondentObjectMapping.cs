using AutoMapper;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Complaint;

namespace Project.Gawad.Application.Profiles.Complaints
{
    public class RespondentToRespondentObjectMapping : Profile
    {
        public RespondentToRespondentObjectMapping()
        {
            CreateMap<Respondent, RespondentObject>()
                .ForMember(d => d.Id, o => o.MapFrom(s => s.Id))
                .ForMember(d => d.PersonId, o => o.MapFrom(s => s.PersonId))
                .ForMember(d => d.FirstName, o => o.MapFrom(s => s.Person.FirstName))
                .ForMember(d => d.LastName, o => o.MapFrom(s => s.Person.LastName))
                .ForMember(d => d.MiddleName, o => o.MapFrom(s => s.Person.MiddleName))
                .ForMember(d => d.Address, o => o.MapFrom(s => s.Address != null ? 
                    $"{s.Address.AddressLine1}, {s.Address.Barangay}, {s.Address.City}, {s.Address.Province}" : 
                    string.Empty))
                .ForMember(d => d.Age, o => o.MapFrom(s => s.Age))
                .ForMember(d => d.Gender, o => o.MapFrom(s => s.Gender))
                .ForMember(d => d.CivilStatus, o => o.MapFrom(s => s.CivilStatus))
                .ForMember(d => d.Occupation, o => o.MapFrom(s => s.Occupation));
        }
    }
}
