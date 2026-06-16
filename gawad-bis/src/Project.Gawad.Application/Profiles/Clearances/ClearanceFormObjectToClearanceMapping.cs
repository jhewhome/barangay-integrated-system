using AutoMapper;
using MongoDB.Bson;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums.Transactions;
using Project.Gawad.Domain.Objects.Clearance;

namespace Project.Gawad.Application.Profiles.Clearances;

public class ClearanceFormObjectToClearanceMapping : Profile
{
    public ClearanceFormObjectToClearanceMapping()
    {
        CreateMap<ClearanceFormObject, Clearance>()
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

            // Transaction Details
            .ForMember(dst => dst.Purpose,
                src => src.MapFrom(x => x.Purpose))
            .ForMember(dst => dst.ClearancePurpose,
                src => src.MapFrom(x => x.ClearancePurpose))
            .ForMember(dst => dst.BarangayTrasaction,
                opt =>
                {
                    opt.MapFrom((src, dest, context) =>
                    {
                        return new BarangayTrasaction
                        {
                            Id = ObjectId.GenerateNewId(),
                            Fee = src.Fee,
                            Type = TransactionType.BarangayClearance,
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