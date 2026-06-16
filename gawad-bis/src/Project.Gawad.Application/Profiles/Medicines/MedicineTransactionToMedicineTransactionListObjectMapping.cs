using AutoMapper;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Medicine;

namespace Project.Gawad.Application.Profiles.Medicines;

public class MedicineTransactionToMedicineTransactionListObjectMapping : Profile
{
    public MedicineTransactionToMedicineTransactionListObjectMapping()
    {
        CreateMap<MedicineTransaction, MedicineTransactionListObject>()
            .ForMember(dst => dst.Id, opt => opt.MapFrom(src => src.Id!.Value))
            .ForMember(dst => dst.TransactionType, opt => opt.MapFrom(src => src.TransactionType))
            .ForMember(dst => dst.Quantity, opt => opt.MapFrom(src => src.Quantity))
            .ForMember(dst => dst.TransactionDate, opt => opt.MapFrom(src => src.TransactionDate))
            .ForMember(dst => dst.RecipientName, opt => opt.MapFrom(src => src.RecipientName))
            .ForMember(dst => dst.Reason, opt => opt.MapFrom(src => src.Reason))
            .ForMember(dst => dst.Notes, opt => opt.MapFrom(src => src.Notes))
            .ForMember(dst => dst.UnitPrice, opt => opt.MapFrom(src => src.UnitPrice))
            .ForMember(dst => dst.TotalAmount, opt => opt.MapFrom(src => src.TotalAmount));
    }
}




