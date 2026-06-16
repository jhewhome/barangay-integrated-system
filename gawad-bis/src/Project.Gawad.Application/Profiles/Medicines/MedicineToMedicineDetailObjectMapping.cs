using AutoMapper;
using Project.Gawad.Core.Extensions;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Medicine;

namespace Project.Gawad.Application.Profiles.Medicines;

public class MedicineToMedicineDetailObjectMapping : Profile
{
    public MedicineToMedicineDetailObjectMapping()
    {
        CreateMap<Medicine, MedicineDetailObject>()
            .ForMember(dst => dst.Id, opt => opt.MapFrom(src => src.Id!.Value))
            .ForMember(dst => dst.Name, opt => opt.MapFrom(src => src.Name))
            .ForMember(dst => dst.Description, opt => opt.MapFrom(src => src.Description))
            .ForMember(dst => dst.Dosage, opt => opt.MapFrom(src => src.Dosage))
            .ForMember(dst => dst.DosageType, opt => opt.MapFrom(src => src.DosageType))
            .ForMember(dst => dst.Category, opt => opt.MapFrom(src => src.Category))
            .ForMember(dst => dst.CategoryName, opt => opt.MapFrom(src => src.Category.GetEnumDisplayName()))
            .ForMember(dst => dst.UnitOfMeasure, opt => opt.MapFrom(src => src.UnitOfMeasure))
            .ForMember(dst => dst.UnitOfMeasureName, opt => opt.MapFrom(src => src.UnitOfMeasure.GetEnumDisplayName()))
            .ForMember(dst => dst.Manufacturer, opt => opt.MapFrom(src => src.Manufacturer))
            .ForMember(dst => dst.GenericName, opt => opt.MapFrom(src => src.GenericName))
            .ForMember(dst => dst.UnitPrice, opt => opt.MapFrom(src => src.UnitPrice))
            .ForMember(dst => dst.MinimumStockLevel, opt => opt.MapFrom(src => src.MinimumStockLevel))
            .ForMember(dst => dst.IsPrescriptionRequired, opt => opt.MapFrom(src => src.IsPrescriptionRequired))
            .ForMember(dst => dst.IsActive, opt => opt.MapFrom(src => src.IsActive))
            .ForMember(dst => dst.Notes, opt => opt.MapFrom(src => src.Notes))
            .ForMember(dst => dst.IsLimitedSupply, opt => opt.MapFrom(src => src.IsLimitedSupply))
            .ForMember(dst => dst.AllocationPeriod, opt => opt.MapFrom(src => src.AllocationPeriod))
            .ForMember(dst => dst.MaxQuantityPerPeriod, opt => opt.MapFrom(src => src.MaxQuantityPerPeriod))
            .ForMember(dst => dst.BottleMeasurementType, opt => opt.MapFrom(src => src.BottleMeasurementType))
            .ForMember(dst => dst.BottleMeasurementValue, opt => opt.MapFrom(src => src.BottleMeasurementValue));
    }
}




