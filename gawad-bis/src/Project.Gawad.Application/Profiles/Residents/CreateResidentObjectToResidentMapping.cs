using AutoMapper;
using MongoDB.Bson;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums;
using Project.Gawad.Domain.Objects.Resident;

namespace Project.Gawad.Application.Profiles.Residents;

public class CreateResidentObjectToResidentMapping : Profile
{
    public CreateResidentObjectToResidentMapping()
    {
        CreateMap<CreateResidentObject, Resident>()
            .ForMember(dst => dst.Id, opt => { opt.MapFrom((src, dest, context) => ObjectId.GenerateNewId()); })
            .ForMember(dst => dst.Person,
                opt =>
                {
                    opt.MapFrom((src, dest, context) => new Person
                    {
                        Id = ObjectId.GenerateNewId(),
                        FirstName = src.FirstName,
                        LastName = src.LastName,
                        MiddleName = src.MiddleName,
                        Suffix = src.Suffix,
                        DateOfBirth = src.DateOfBirth.Value,
                        PlaceOfBirth = src.PlaceOfBirth,
                        Gender = src.Gender,
                        CivilStatus = src.CivilStatus,
                        SpouseName = src.SpouseName,
                        FatherName = src.FatherName,
                        MotherMaidenName = src.MotherMaidenName,
                        IsResident = true,
                        Nationality = src.Nationality
                    });
                })
            .ForMember(dst => dst.Addresses,
                src =>
                {
                    src.MapFrom(x => new List<Address>
                    {
                        new()
                        {
                            Id = ObjectId.GenerateNewId(),
                            AddressLine1 = x.PermAddAddressLine1,
                            AddressLine2 = x.PermAddAddressLine2,
                            Zone = x.PermAddZone,
                            Barangay = x.PermAddBarangay,
                            City = x.PermAddCity,
                            Province = x.PermAddProvince,
                            ZipCode = x.PermAddZipCode,
                            Country = x.PermAddCountry,
                            Type = AddressType.Permanent,
                            CreatedDate = DateTime.UtcNow
                        },
                        new()
                        {
                            Id = ObjectId.GenerateNewId(),
                            AddressLine1 = x.CurrAddAddressLine1,
                            AddressLine2 = x.CurrAddAddressLine2,
                            Zone = x.CurrAddZone,
                            Barangay = x.CurrAddBarangay,
                            City = x.CurrAddCity,
                            Province = x.CurrAddProvince,
                            ZipCode = x.CurrAddZipCode,
                            Country = x.CurrAddCountry,
                            Type = AddressType.Current,
                            CreatedDate = DateTime.UtcNow
                        }
                    });
                })
            .ForMember(dst => dst.PrecintNo,
                src => src.MapFrom(x => x.PrecintNo))
            .ForMember(dst => dst.VoterId,
                src => src.MapFrom(x => x.VoterId))
            .ForMember(dst => dst.IsPWD,
                src => src.MapFrom(x => x.IsPWD))
            .ForMember(dst => dst.IsRegisteredVoter,
                src => src.MapFrom(x => x.IsRegisteredVoter))
            .ForMember(dst => dst.CreatedDate,
                src => src.MapFrom(src => DateTime.UtcNow))
            .ForMember(dst => dst.RegistratedDate,
                src => src.MapFrom(src => DateTime.UtcNow))
            ;
    }
}