using AutoMapper;
using MongoDB.Bson;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Complaint;

namespace Project.Gawad.Application.Profiles.Complaints
{
    public class ComplainantToComplainantObjectMapping : Profile
    {
        public ComplainantToComplainantObjectMapping()
        {
            CreateMap<Complainant, ComplainantObject>()
                .ForMember(d => d.Id, o => o.MapFrom(s => s.Id))
                .ForMember(d => d.PersonId, o => o.MapFrom(s => s.PersonId))
                .ForMember(d => d.ContactNumber, o => o.MapFrom(s => string.Empty)) // Default empty since not in entity
                .ForMember(d => d.FirstName, o => o.MapFrom(s => s.Person.FirstName))
                .ForMember(d => d.LastName, o => o.MapFrom(s => s.Person.LastName))
                .ForMember(d => d.MiddleName, o => o.MapFrom(s => s.Person.MiddleName))
                .ForMember(d => d.Suffix, o => o.MapFrom(s => s.Person.Suffix))
                .ForMember(d => d.DateOfBirth, o => o.MapFrom(s => s.Person.DateOfBirth))
                .ForMember(d => d.PlaceOfBirth, o => o.MapFrom(s => s.Person.PlaceOfBirth))
                .ForMember(d => d.Gender, o => o.MapFrom(s => s.Person.Gender))
                .ForMember(d => d.CivilStatus, o => o.MapFrom(s => s.Person.CivilStatus))
                .ForMember(d => d.SpouseName, o => o.MapFrom(s => s.Person.SpouseName))
                .ForMember(d => d.FatherName, o => o.MapFrom(s => s.Person.FatherName))
                .ForMember(d => d.MotherMaidenName, o => o.MapFrom(s => s.Person.MotherMaidenName))
                .ForMember(d => d.VoterId, o => o.MapFrom(s => string.Empty)) // Default empty since not in entity
                .ForMember(d => d.PrecintNo, o => o.MapFrom(s => string.Empty)) // Default empty since not in entity
                .ForMember(d => d.CreatedBy, o => o.MapFrom(s => s.CreatedById ?? ObjectId.Empty))
                .ForMember(d => d.ModifiedBy, o => o.MapFrom(s => s.LastModifiedById ?? ObjectId.Empty));
        }
    }
}
