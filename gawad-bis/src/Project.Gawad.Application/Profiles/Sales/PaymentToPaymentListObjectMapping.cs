using AutoMapper;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Sales;

namespace Project.Gawad.Application.Profiles.Sales;

public class PaymentToPaymentListObjectMapping : Profile
{
    public PaymentToPaymentListObjectMapping()
    {
        CreateMap<Payment, PaymentListObject>()
            .ForMember(dst => dst.Id, opt => opt.MapFrom(src => src.Id!.Value.ToString()))
            .ForMember(dst => dst.SaleId, opt => opt.MapFrom(src => src.SaleId.ToString()))
            .ForMember(dst => dst.PaymentMethod, opt => opt.MapFrom(src => src.PaymentMethod))
            .ForMember(dst => dst.Amount, opt => opt.MapFrom(src => src.Amount))
            .ForMember(dst => dst.Change, opt => opt.MapFrom(src => src.Change))
            .ForMember(dst => dst.PaymentDate, opt => opt.MapFrom(src => src.PaymentDate))
            .ForMember(dst => dst.ReferenceNumber, opt => opt.MapFrom(src => src.ReferenceNumber))
            .ForMember(dst => dst.Notes, opt => opt.MapFrom(src => src.Notes));
    }
}



