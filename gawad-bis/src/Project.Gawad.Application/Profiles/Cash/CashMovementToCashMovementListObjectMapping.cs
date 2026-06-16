using AutoMapper;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Cash;

namespace Project.Gawad.Application.Profiles.Cash;

public class CashMovementToCashMovementListObjectMapping : Profile
{
    public CashMovementToCashMovementListObjectMapping()
    {
        CreateMap<CashMovement, CashMovementListObject>()
            .ForMember(dst => dst.Id, opt => opt.MapFrom(src => src.Id!.Value.ToString()))
            .ForMember(dst => dst.CashSessionId, opt => opt.MapFrom(src => src.CashSessionId.ToString()))
            .ForMember(dst => dst.MovementType, opt => opt.MapFrom(src => src.MovementType))
            .ForMember(dst => dst.Amount, opt => opt.MapFrom(src => src.Amount))
            .ForMember(dst => dst.ReferenceSaleId, opt => opt.MapFrom(src => src.ReferenceSaleId.HasValue ? src.ReferenceSaleId.Value.ToString() : null))
            .ForMember(dst => dst.ReferencePaymentId, opt => opt.MapFrom(src => src.ReferencePaymentId.HasValue ? src.ReferencePaymentId.Value.ToString() : null))
            .ForMember(dst => dst.MovementDate, opt => opt.MapFrom(src => src.MovementDate))
            .ForMember(dst => dst.Notes, opt => opt.MapFrom(src => src.Notes))
            .ForMember(dst => dst.Reason, opt => opt.MapFrom(src => src.Reason))
            .ForMember(dst => dst.CreatedByName, opt => opt.Ignore());
    }
}



