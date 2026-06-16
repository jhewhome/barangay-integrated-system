using AutoMapper;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Sales;

namespace Project.Gawad.Application.Profiles.Sales;

public class SaleItemToSaleItemListObjectMapping : Profile
{
    public SaleItemToSaleItemListObjectMapping()
    {
        CreateMap<SaleItem, SaleItemListObject>()
            .ForMember(dst => dst.Id, opt => opt.MapFrom(src => src.Id!.Value.ToString()))
            .ForMember(dst => dst.MedicineId, opt => opt.MapFrom(src => src.MedicineId.ToString()))
            .ForMember(dst => dst.MedicineName, opt => opt.Ignore())
            .ForMember(dst => dst.MedicineStockId, opt => opt.MapFrom(src => src.MedicineStockId.HasValue ? src.MedicineStockId.Value.ToString() : null))
            .ForMember(dst => dst.Quantity, opt => opt.MapFrom(src => src.Quantity))
            .ForMember(dst => dst.UnitPrice, opt => opt.MapFrom(src => src.UnitPrice))
            .ForMember(dst => dst.DiscountAmount, opt => opt.MapFrom(src => src.DiscountAmount))
            .ForMember(dst => dst.LineTotal, opt => opt.MapFrom(src => src.LineTotal))
            .ForMember(dst => dst.Notes, opt => opt.MapFrom(src => src.Notes));
    }
}



