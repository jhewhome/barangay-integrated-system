using AutoMapper;
using MongoDB.Bson;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Medicine;

namespace Project.Gawad.Application.Profiles.Medicines;

public class CreateMedicineObjectToMedicineMapping : Profile
{
    public CreateMedicineObjectToMedicineMapping()
    {
        CreateMap<CreateMedicineObject, Medicine>()
            .ForMember(dst => dst.Id, opt => { opt.MapFrom((src, dest, context) => src.Id ?? ObjectId.GenerateNewId()); })
            .ForMember(dst => dst.CreatedDate, opt => opt.MapFrom(src => DateTime.UtcNow))
            .ForMember(dst => dst.Name, opt => opt.MapFrom(src => src.Name))
            .ForMember(dst => dst.Description, opt => opt.MapFrom(src => src.Description))
            .ForMember(dst => dst.Dosage, opt => opt.MapFrom(src => src.Dosage))
            .ForMember(dst => dst.DosageType, opt => opt.MapFrom(src => src.DosageType))
            .ForMember(dst => dst.Category, opt => opt.MapFrom(src => src.Category))
            .ForMember(dst => dst.UnitOfMeasure, opt => opt.MapFrom(src => src.UnitOfMeasure))
            .ForMember(dst => dst.Manufacturer, opt => opt.MapFrom(src => src.Manufacturer))
            .ForMember(dst => dst.GenericName, opt => opt.MapFrom(src => src.GenericName))
            .ForMember(dst => dst.UnitPrice, opt => opt.MapFrom(src => src.UnitPrice))
            .ForMember(dst => dst.MinimumStockLevel, opt => opt.MapFrom(src => src.MinimumStockLevel))
            .ForMember(dst => dst.IsPrescriptionRequired, opt => opt.MapFrom(src => src.IsPrescriptionRequired))
            .ForMember(dst => dst.IsActive, opt => opt.MapFrom(src => src.IsActive))
            .ForMember(dst => dst.Notes, opt => opt.MapFrom(src => src.Notes))
            .ForMember(dst => dst.BottleMeasurementType, opt => opt.MapFrom(src => src.BottleMeasurementType))
            .ForMember(dst => dst.BottleMeasurementValue, opt => opt.MapFrom(src => src.BottleMeasurementValue));
    }
}




