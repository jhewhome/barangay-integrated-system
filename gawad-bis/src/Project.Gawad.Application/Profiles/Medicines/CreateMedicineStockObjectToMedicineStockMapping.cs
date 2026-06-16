using AutoMapper;
using MongoDB.Bson;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Medicine;

namespace Project.Gawad.Application.Profiles.Medicines;

public class CreateMedicineStockObjectToMedicineStockMapping : Profile
{
    public CreateMedicineStockObjectToMedicineStockMapping()
    {
        CreateMap<CreateMedicineStockObject, MedicineStock>()
            .ForMember(dst => dst.Id, opt => { opt.MapFrom((src, dest, context) => src.Id ?? ObjectId.GenerateNewId()); })
            .ForMember(dst => dst.CreatedDate, opt => opt.MapFrom(src => DateTime.UtcNow))
            .ForMember(dst => dst.MedicineId, opt => opt.MapFrom(src => src.MedicineId))
            .ForMember(dst => dst.Quantity, opt => opt.MapFrom(src => src.Quantity))
            .ForMember(dst => dst.ExpiryDate, opt => opt.MapFrom(src => src.ExpiryDate))
            .ForMember(dst => dst.BatchNumber, opt => opt.MapFrom(src => src.BatchNumber))
            .ForMember(dst => dst.LotNumber, opt => opt.MapFrom(src => src.LotNumber))
            .ForMember(dst => dst.CostPerUnit, opt => opt.MapFrom(src => src.CostPerUnit))
            .ForMember(dst => dst.Supplier, opt => opt.MapFrom(src => src.Supplier))
            .ForMember(dst => dst.ReceivedDate, opt => opt.MapFrom(src => src.ReceivedDate ?? DateTime.UtcNow))
            .ForMember(dst => dst.ManufacturingDate, opt => opt.MapFrom(src => src.ManufacturingDate))
            .ForMember(dst => dst.Location, opt => opt.MapFrom(src => src.Location))
            .ForMember(dst => dst.Notes, opt => opt.MapFrom(src => src.Notes));
    }
}




