using AutoMapper;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Cash;

namespace Project.Gawad.Application.Profiles.Cash;

public class CashSessionToCashSessionDetailObjectMapping : Profile
{
    public CashSessionToCashSessionDetailObjectMapping()
    {
        CreateMap<CashSession, CashSessionDetailObject>()
            .ForMember(dst => dst.Id, opt => opt.MapFrom(src => src.Id!.Value.ToString()))
            .ForMember(dst => dst.SessionDate, opt => opt.MapFrom(src => src.SessionDate))
            .ForMember(dst => dst.Status, opt => opt.MapFrom(src => src.Status))
            .ForMember(dst => dst.OpenedById, opt => opt.MapFrom(src => src.OpenedById))
            .ForMember(dst => dst.OpenedByName, opt => opt.Ignore())
            .ForMember(dst => dst.OpenedAt, opt => opt.MapFrom(src => src.OpenedAt))
            .ForMember(dst => dst.OpeningFloat, opt => opt.MapFrom(src => src.OpeningFloat))
            .ForMember(dst => dst.ClosedById, opt => opt.MapFrom(src => src.ClosedById))
            .ForMember(dst => dst.ClosedByName, opt => opt.Ignore())
            .ForMember(dst => dst.ClosedAt, opt => opt.MapFrom(src => src.ClosedAt))
            .ForMember(dst => dst.ClosingAmount, opt => opt.MapFrom(src => src.ClosingAmount))
            .ForMember(dst => dst.ExpectedAmount, opt => opt.MapFrom(src => src.ExpectedAmount))
            .ForMember(dst => dst.Variance, opt => opt.MapFrom(src => src.Variance))
            .ForMember(dst => dst.ClosingNotes, opt => opt.MapFrom(src => src.ClosingNotes))
            .ForMember(dst => dst.TotalSales, opt => opt.Ignore())
            .ForMember(dst => dst.TotalPayments, opt => opt.Ignore())
            .ForMember(dst => dst.SaleCount, opt => opt.Ignore())
            .ForMember(dst => dst.Movements, opt => opt.Ignore());
    }
}



