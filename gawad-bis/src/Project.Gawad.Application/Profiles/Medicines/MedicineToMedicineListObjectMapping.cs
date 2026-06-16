using AutoMapper;
using Project.Gawad.Core.Extensions;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Medicine;

namespace Project.Gawad.Application.Profiles.Medicines;

public class MedicineToMedicineListObjectMapping : Profile
{
    public MedicineToMedicineListObjectMapping()
    {
        CreateMap<Medicine, MedicineListObject>()
            .ForMember(dst => dst.Id, opt => opt.MapFrom(src => src.Id!.Value.ToString()))
            .ForMember(dst => dst.Name, opt => opt.MapFrom(src => src.Name))
            .ForMember(dst => dst.Category, opt => opt.MapFrom(src => src.Category.GetEnumDisplayName()))
            .ForMember(dst => dst.UnitOfMeasure, opt => opt.MapFrom(src => src.UnitOfMeasure.GetEnumDisplayName()))
            .ForMember(dst => dst.MinimumStockLevel, opt => opt.MapFrom(src => src.MinimumStockLevel))
            .ForMember(dst => dst.IsActive, opt => opt.MapFrom(src => src.IsActive))
            .ForMember(dst => dst.UnitPrice, opt => opt.MapFrom(src => src.UnitPrice))
            .ForMember(dst => dst.BottleMeasurementType, opt => opt.MapFrom(src => src.BottleMeasurementType))
            .ForMember(dst => dst.BottleMeasurementValue, opt => opt.MapFrom(src => src.BottleMeasurementValue));
    }
}




