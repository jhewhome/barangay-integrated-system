using AutoMapper;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Medicine;

namespace Project.Gawad.Application.Profiles.Medicines;

public class MedicineStockToMedicineStockListObjectMapping : Profile
{
    public MedicineStockToMedicineStockListObjectMapping()
    {
        CreateMap<MedicineStock, MedicineStockListObject>()
            .ForMember(dst => dst.Id, opt => opt.MapFrom(src => src.Id!.Value))
            .ForMember(dst => dst.Quantity, opt => opt.MapFrom(src => src.Quantity))
            .ForMember(dst => dst.ExpiryDate, opt => opt.MapFrom(src => src.ExpiryDate))
            .ForMember(dst => dst.BatchNumber, opt => opt.MapFrom(src => src.BatchNumber))
            .ForMember(dst => dst.LotNumber, opt => opt.MapFrom(src => src.LotNumber))
            .ForMember(dst => dst.Location, opt => opt.MapFrom(src => src.Location))
            .ForMember(dst => dst.NotificationDate, opt => opt.MapFrom(src => src.NotificationDate))
            .ForMember(dst => dst.IsNotified, opt => opt.MapFrom(src => src.NotificationDate.HasValue))
            .ForMember(dst => dst.ActionTaken, opt => opt.MapFrom(src => src.ActionTaken))
            .ForMember(dst => dst.ActionDate, opt => opt.MapFrom(src => src.ActionDate))
            .ForMember(dst => dst.ActionNotes, opt => opt.MapFrom(src => src.ActionNotes))
            .ForMember(dst => dst.HasAction, opt => opt.MapFrom(src => !string.IsNullOrEmpty(src.ActionTaken)));
    }
}




