using AutoMapper;
using MongoDB.Bson;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums.Transactions;
using Project.Gawad.Domain.Objects.BusinessPermit;

namespace Project.Gawad.Application.Profiles.BusinessPermits;

public class BusinessPermitFormObjectToBusinessPermitMapping : Profile
{
    public BusinessPermitFormObjectToBusinessPermitMapping()
    {
        CreateMap<BusinessPermitFormObject, BusinessPermit>()
            .ForMember(dst => dst.Id,
                opt => { opt.MapFrom((src, dest, context) => ObjectId.GenerateNewId()); })
            .ForMember(dst => dst.Person,
                opt =>
                {
                    opt.MapFrom((src, dest, context) =>
                    {
                        return new Person
                        {
                            Id = ObjectId.GenerateNewId(),
                            FirstName = src.FirstName,
                            LastName = src.LastName,
                            MiddleName = src.MiddleName,
                            Suffix = src.Suffix,
                            DateOfBirth = src.DateOfBirth!.Value,
                            PlaceOfBirth = src.PlaceOfBirth,
                            Gender = src.Gender,
                            CivilStatus = src.CivilStatus,
                            SpouseName = src.SpouseName,
                            FatherName = src.FatherName,
                            MotherMaidenName = src.MotherMaidenName,
                            Nationality = src.Nationality
                        };
                    });
                })

            // Address
            .ForMember(dst => dst.AddressLine1,
                src => src.MapFrom(x => x.AddressLine1))
            .ForMember(dst => dst.AddressLine2,
                src => src.MapFrom(x => x.AddressLine2))
            .ForMember(dst => dst.Barangay,
                src => src.MapFrom(x => x.Barangay))
            .ForMember(dst => dst.Zone,
                src => src.MapFrom(x => x.Zone))
            .ForMember(dst => dst.City,
                src => src.MapFrom(x => x.City))
            .ForMember(dst => dst.Province,
                src => src.MapFrom(x => x.Province))
            .ForMember(dst => dst.Country,
                src => src.MapFrom(x => x.Country))
            .ForMember(dst => dst.ZipCode,
                src => src.MapFrom(x => x.ZipCode))

            // Address
            .ForMember(dst => dst.BusinessAddressLine1,
                src => src.MapFrom(x => x.BussAddressLine1))
            .ForMember(dst => dst.BusinessAddressLine2,
                src => src.MapFrom(x => x.BussAddressLine2))
            .ForMember(dst => dst.BusinessBarangay,
                src => src.MapFrom(x => x.BussBarangay))
            .ForMember(dst => dst.BusinessZone,
                src => src.MapFrom(x => x.BussZone))
            .ForMember(dst => dst.BusinessCity,
                src => src.MapFrom(x => x.BussCity))
            .ForMember(dst => dst.BusinessProvince,
                src => src.MapFrom(x => x.BussProvince))
            .ForMember(dst => dst.BusinessCountry,
                src => src.MapFrom(x => x.BussCountry))
            .ForMember(dst => dst.BusinessZipCode,
                src => src.MapFrom(x => x.BussZipCode))

            // Transaction Details
            .ForMember(dst => dst.BarangayTrasaction,
                opt =>
                {
                    opt.MapFrom((src, dest, context) =>
                    {
                        return new BarangayTrasaction
                        {
                            Id = ObjectId.GenerateNewId(),
                            Fee = src.Fee,
                            Type = TransactionType.BusinessPermit,
                            CreatedDate = DateTime.UtcNow,
                            Notes = src.Notes,
                            ReceiptNumber = src.ReceiptNumber,
                            OfficerOfTheDay = src.OfficerOfTheDay
                        };
                    });
                })
            .ForMember(dst => dst.CreatedDate,
                opt => { opt.MapFrom(src => DateTime.UtcNow); })
            .ForMember(dst => dst.ApplicationDate,
                opt => { opt.MapFrom(src => DateTime.UtcNow); })
            ;
    }
}