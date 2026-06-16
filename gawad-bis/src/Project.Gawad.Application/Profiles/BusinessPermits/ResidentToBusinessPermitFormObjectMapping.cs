using AutoMapper;
using Project.Gawad.Application.Utils;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums;
using Project.Gawad.Domain.Objects.BusinessPermit;

namespace Project.Gawad.Application.Profiles.BusinessPermits;

public class ResidentToBusinessPermitFormObjectMapping : Profile
{
    public ResidentToBusinessPermitFormObjectMapping()
    {
        CreateMap<Resident, BusinessPermitFormObject>()
            .ForMember(dst => dst.PersonId,
                src => src.MapFrom(s => s.Person.Id))
            .ForMember(dst => dst.LastName,
                src => src.MapFrom(s => s.Person.LastName))
            .ForMember(dst => dst.FirstName,
                src => src.MapFrom(s => s.Person.FirstName))
            .ForMember(dst => dst.MiddleName,
                src => src.MapFrom(s => s.Person.MiddleName))
            .ForMember(dst => dst.Suffix,
                src => src.MapFrom(s => s.Person.Suffix))
            .ForMember(dst => dst.DateOfBirth,
                src => src.MapFrom(s => s.Person.DateOfBirth))
            .ForMember(dst => dst.PlaceOfBirth,
                src => src.MapFrom(s => s.Person.PlaceOfBirth))
            .ForMember(dst => dst.Gender,
                src => src.MapFrom(s => s.Person.Gender))
            .ForMember(dst => dst.CivilStatus,
                src => src.MapFrom(s => s.Person.CivilStatus))
            .ForMember(dst => dst.FatherName,
                src => src.MapFrom(s => s.Person.FatherName))
            .ForMember(dst => dst.MotherMaidenName,
                src => src.MapFrom(s => s.Person.MotherMaidenName))
            .ForMember(dst => dst.SpouseName,
                src => src.MapFrom(s => s.Person.SpouseName))
            .ForMember(dst => dst.Nationality,
                src => src.MapFrom(s => s.Person.Nationality))
            
            .ForMember(dst => dst.AddressLine1,
                src => src.MapFrom(
                    x => AddressHelper.GetAddressValue(x, AddressValue.AddressLine1, AddressType.Current)))
            .ForMember(dst => dst.AddressLine2,
                src => src.MapFrom(
                    x => AddressHelper.GetAddressValue(x, AddressValue.AddressLine2, AddressType.Current)))
            .ForMember(dst => dst.Zone,
                src => src.MapFrom(
                    x => AddressHelper.GetAddressValue(x, AddressValue.Zone, AddressType.Current)))
            .ForMember(dst => dst.Barangay,
                src => src.MapFrom(
                    x => AddressHelper.GetAddressValue(x, AddressValue.Barangay, AddressType.Current)))
            .ForMember(dst => dst.City,
                src => src.MapFrom(
                    x => AddressHelper.GetAddressValue(x, AddressValue.City, AddressType.Current)))
            .ForMember(dst => dst.Province,
                src => src.MapFrom(
                    x => AddressHelper.GetAddressValue(x, AddressValue.Province, AddressType.Current)))
            .ForMember(dst => dst.ZipCode,
                src => src.MapFrom(
                    x => AddressHelper.GetAddressValue(x, AddressValue.ZipCode, AddressType.Current)))
            .ForMember(dst => dst.Country,
                src => src.MapFrom(
                    x => AddressHelper.GetAddressValue(x, AddressValue.Country, AddressType.Current)))
            
            ;
    }
}