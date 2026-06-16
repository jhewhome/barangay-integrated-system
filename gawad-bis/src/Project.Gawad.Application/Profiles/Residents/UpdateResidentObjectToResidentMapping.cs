using AutoMapper;
using MongoDB.Bson;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums;
using Project.Gawad.Domain.Objects.Resident;

namespace Project.Gawad.Application.Profiles.Residents;

public class UpdateResidentObjectToResidentMapping : Profile
{
    public UpdateResidentObjectToResidentMapping()
    {
        CreateMap<UpdateResidentObject, Resident>()
            .ForMember(dst => dst.Id, opt => { opt.MapFrom((src, dest, context) => src.Id); })
            .ForMember(dst => dst.PersonId,
                opt => opt.MapFrom((src, dest, context) => dest.PersonId))
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
                opt => { opt.MapFrom((src, dest, context) => dest.CreatedDate); })
            .ForMember(dst => dst.LastModifiedDate,
                opt => { opt.MapFrom((src, dest, context) => DateTime.UtcNow); })
            .ForMember(dst => dst.RegistratedDate,
                opt => { opt.MapFrom((src, dest, context) => dest.RegistratedDate); })
            ;
    }
}