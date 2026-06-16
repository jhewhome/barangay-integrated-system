using AutoMapper;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Resident;

namespace Project.Gawad.Application.Profiles.Residents;

public class UpdateResidentObjectToPersonMapping : Profile
{
    public UpdateResidentObjectToPersonMapping()
    {
        CreateMap<UpdateResidentObject, Person>()
            .ForMember(dst => dst.Id, opt =>
                opt.MapFrom((src, dest, context) => dest.Id))
            .ForMember(dst => dst.FirstName, opt =>
                opt.MapFrom(src => src.FirstName))
            .ForMember(dst => dst.MiddleName, opt =>
                opt.MapFrom(src => src.MiddleName))
            .ForMember(dst => dst.LastName, opt =>
                opt.MapFrom(src => src.LastName))
            .ForMember(dst => dst.Suffix, opt =>
                opt.MapFrom(src => src.Suffix))
            .ForMember(dst => dst.DateOfBirth, opt =>
                opt.MapFrom(src => src.DateOfBirth))
            .ForMember(dst => dst.PlaceOfBirth, opt =>
                opt.MapFrom(src => src.PlaceOfBirth))
            .ForMember(dst => dst.Gender, opt => opt.MapFrom(src => src.Gender))
            .ForMember(dst => dst.CivilStatus, opt =>
                opt.MapFrom(src => src.CivilStatus))
            .ForMember(dst => dst.SpouseName, opt =>
                opt.MapFrom(src => src.SpouseName))
            .ForMember(dst => dst.FatherName, opt =>
                opt.MapFrom(src => src.FatherName))
            .ForMember(dst => dst.MotherMaidenName, opt =>
                opt.MapFrom(src => src.MotherMaidenName))
            .ForMember(dst => dst.Nationality, opt =>
                opt.MapFrom(src => src.Nationality))
            .ForMember(dst => dst.IsResident, opt =>
                opt.MapFrom(src => true))
            .ForMember(dst => dst.LastModifiedDate, opt =>
                opt.MapFrom(src => DateTime.UtcNow))
            ;
    }
}