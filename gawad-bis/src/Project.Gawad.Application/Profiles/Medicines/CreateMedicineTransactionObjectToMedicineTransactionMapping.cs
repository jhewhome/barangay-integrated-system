using AutoMapper;
using MongoDB.Bson;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Medicine;

namespace Project.Gawad.Application.Profiles.Medicines;

public class CreateMedicineTransactionObjectToMedicineTransactionMapping : Profile
{
    public CreateMedicineTransactionObjectToMedicineTransactionMapping()
    {
        CreateMap<CreateMedicineTransactionObject, MedicineTransaction>()
            .ForMember(dst => dst.Id, opt => { opt.MapFrom((src, dest, context) => src.Id ?? ObjectId.GenerateNewId()); })
            .ForMember(dst => dst.CreatedDate, opt => opt.MapFrom(src => DateTime.UtcNow))
            .ForMember(dst => dst.MedicineId, opt => opt.MapFrom(src => src.MedicineId))
            .ForMember(dst => dst.MedicineStockId, opt => opt.MapFrom(src => src.MedicineStockId))
            .ForMember(dst => dst.TransactionType, opt => opt.MapFrom(src => src.TransactionType))
            .ForMember(dst => dst.Quantity, opt => opt.MapFrom(src => src.Quantity))
            .ForMember(dst => dst.TransactionDate, opt => opt.MapFrom(src => src.TransactionDate))
            .ForMember(dst => dst.RecipientPersonId, opt => opt.MapFrom(src => src.RecipientPersonId))
            .ForMember(dst => dst.RecipientName, opt => opt.MapFrom(src => src.RecipientName))
            .ForMember(dst => dst.Reason, opt => opt.MapFrom(src => src.Reason))
            .ForMember(dst => dst.Notes, opt => opt.MapFrom(src => src.Notes))
            .ForMember(dst => dst.UnitPrice, opt => opt.MapFrom(src => src.UnitPrice));
    }
}




