using AutoMapper;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Sales;

namespace Project.Gawad.Application.Profiles.Sales;

public class SaleToSaleDetailObjectMapping : Profile
{
    public SaleToSaleDetailObjectMapping()
    {
        CreateMap<Sale, SaleDetailObject>()
            .ForMember(dst => dst.Id, opt => opt.MapFrom(src => src.Id!.Value.ToString()))
            .ForMember(dst => dst.SaleDate, opt => opt.MapFrom(src => src.SaleDate))
            .ForMember(dst => dst.Status, opt => opt.MapFrom(src => src.Status))
            .ForMember(dst => dst.CustomerPersonId, opt => opt.MapFrom(src => src.CustomerPersonId.HasValue ? src.CustomerPersonId.Value.ToString() : null))
            .ForMember(dst => dst.CustomerName, opt => opt.MapFrom(src => src.CustomerName))
            .ForMember(dst => dst.Subtotal, opt => opt.MapFrom(src => src.Subtotal))
            .ForMember(dst => dst.DiscountAmount, opt => opt.MapFrom(src => src.DiscountAmount))
            .ForMember(dst => dst.TaxAmount, opt => opt.MapFrom(src => src.TaxAmount))
            .ForMember(dst => dst.TotalAmount, opt => opt.MapFrom(src => src.TotalAmount))
            .ForMember(dst => dst.AmountPaid, opt => opt.MapFrom(src => src.AmountPaid))
            .ForMember(dst => dst.Change, opt => opt.MapFrom(src => src.Change))
            .ForMember(dst => dst.Notes, opt => opt.MapFrom(src => src.Notes))
            .ForMember(dst => dst.Items, opt => opt.Ignore())
            .ForMember(dst => dst.Payments, opt => opt.Ignore())
            .ForMember(dst => dst.CreatedByName, opt => opt.Ignore())
            .ForMember(dst => dst.CreatedDate, opt => opt.MapFrom(src => src.CreatedDate));
    }
}



