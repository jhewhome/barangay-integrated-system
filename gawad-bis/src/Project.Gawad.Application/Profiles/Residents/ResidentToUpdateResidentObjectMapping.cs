using AutoMapper;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums;
using Project.Gawad.Domain.Objects.Resident;

namespace Project.Gawad.Application.Profiles.Residents;

public class ResidentToUpdateResidentObjectMapping : Profile
{
    public ResidentToUpdateResidentObjectMapping()
    {
        CreateMap<Resident, UpdateResidentObject>()
            .ForMember(dst => dst.Id,
                src => src.MapFrom(x => x.Id))
            .ForMember(dst => dst.PersonId,
                src => src.MapFrom(x => x.Person.Id))
            .ForMember(dst => dst.FirstName,
                src => src.MapFrom(x => x.Person.FirstName))
            .ForMember(dst => dst.LastName,
                src => src.MapFrom(x => x.Person.LastName))
            .ForMember(dst => dst.MiddleName,
                src => src.MapFrom(x => x.Person.MiddleName))
            .ForMember(dst => dst.Suffix,
                src => src.MapFrom(x => x.Person.Suffix))
            .ForMember(dst => dst.DateOfBirth,
                src => src.MapFrom(x => x.Person.DateOfBirth))
            .ForMember(dst => dst.PlaceOfBirth,
                src => src.MapFrom(x => x.Person.PlaceOfBirth))
            .ForMember(dst => dst.Gender,
                src => src.MapFrom(x => x.Person.Gender))
            .ForMember(dst => dst.CivilStatus,
                src => src.MapFrom(x => x.Person.CivilStatus))
            .ForMember(dst => dst.SpouseName,
                src => src.MapFrom(x => x.Person.SpouseName))
            .ForMember(dst => dst.FatherName,
                src => src.MapFrom(x => x.Person.FatherName))
            .ForMember(dst => dst.MotherMaidenName,
                src => src.MapFrom(x => x.Person.MotherMaidenName))
            .ForMember(dst => dst.Nationality,
                src => src.MapFrom(x => x.Person.Nationality))
            .ForMember(dst => dst.VoterId,
                src => src.MapFrom(x => x.VoterId))
            .ForMember(dst => dst.PrecintNo,
                src => src.MapFrom(x => x.PrecintNo))
            .ForMember(dst => dst.PrecintNo,
                src => src.MapFrom(x => x.PrecintNo))
            .ForMember(dst => dst.IsPWD,
                src => src.MapFrom(x => x.IsPWD))
            .ForMember(dst => dst.IsRegisteredVoter,
                src => src.MapFrom(x => x.IsRegisteredVoter))

            // Permanent Address
            .ForMember(dst => dst.PermAddAddressId,
                src => src.MapFrom(x => x.Addresses.FirstOrDefault(a => a.Type == AddressType.Permanent)!.Id))
            .ForMember(dst => dst.PermAddAddressLine1,
                src => src.MapFrom(x => x.Addresses.FirstOrDefault(a => a.Type == AddressType.Permanent)!.AddressLine1))
            .ForMember(dst => dst.PermAddAddressLine2,
                src => src.MapFrom(x => x.Addresses.FirstOrDefault(a => a.Type == AddressType.Permanent)!.AddressLine2))
            .ForMember(dst => dst.PermAddZone,
                src => src.MapFrom(x => x.Addresses.FirstOrDefault(a => a.Type == AddressType.Permanent)!.Zone))
            .ForMember(dst => dst.PermAddBarangay,
                src => src.MapFrom(x => x.Addresses.FirstOrDefault(a => a.Type == AddressType.Permanent)!.Barangay))
            .ForMember(dst => dst.PermAddCity,
                src => src.MapFrom(x => x.Addresses.FirstOrDefault(a => a.Type == AddressType.Permanent)!.City))
            .ForMember(dst => dst.PermAddProvince,
                src => src.MapFrom(x => x.Addresses.FirstOrDefault(a => a.Type == AddressType.Permanent)!.Province))
            .ForMember(dst => dst.PermAddZipCode,
                src => src.MapFrom(x => x.Addresses.FirstOrDefault(a => a.Type == AddressType.Permanent)!.ZipCode))
            .ForMember(dst => dst.PermAddCountry,
                src => src.MapFrom(x => x.Addresses.FirstOrDefault(a => a.Type == AddressType.Permanent)!.Country))

            // Current Address
            .ForMember(dst => dst.CurrAddAddressId,
                src => src.MapFrom(x => x.Addresses.FirstOrDefault(a => a.Type == AddressType.Current)!.Id))
            .ForMember(dst => dst.CurrAddAddressLine1,
                src => src.MapFrom(x => x.Addresses.FirstOrDefault(a => a.Type == AddressType.Current)!.AddressLine1))
            .ForMember(dst => dst.CurrAddAddressLine2,
                src => src.MapFrom(x => x.Addresses.FirstOrDefault(a => a.Type == AddressType.Current)!.AddressLine2))
            .ForMember(dst => dst.CurrAddZone,
                src => src.MapFrom(x => x.Addresses.FirstOrDefault(a => a.Type == AddressType.Current)!.Zone))
            .ForMember(dst => dst.CurrAddBarangay,
                src => src.MapFrom(x => x.Addresses.FirstOrDefault(a => a.Type == AddressType.Current)!.Barangay))
            .ForMember(dst => dst.CurrAddCity,
                src => src.MapFrom(x => x.Addresses.FirstOrDefault(a => a.Type == AddressType.Current)!.City))
            .ForMember(dst => dst.CurrAddProvince,
                src => src.MapFrom(x => x.Addresses.FirstOrDefault(a => a.Type == AddressType.Current)!.Province))
            .ForMember(dst => dst.CurrAddZipCode,
                src => src.MapFrom(x => x.Addresses.FirstOrDefault(a => a.Type == AddressType.Current)!.ZipCode))
            .ForMember(dst => dst.CurrAddCountry,
                src => src.MapFrom(x => x.Addresses.FirstOrDefault(a => a.Type == AddressType.Current)!.Country))
            ;
    }
}