using AutoMapper;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Medicine;

namespace Project.Gawad.Application.Profiles.Medicines;

public class MedicineAuditLogToAuditLogListObjectMapping : Profile
{
    public MedicineAuditLogToAuditLogListObjectMapping()
    {
        CreateMap<MedicineAuditLog, AuditLogListObject>()
            .ForMember(dest => dest.Id, opt => opt.MapFrom(src => src.Id ?? MongoDB.Bson.ObjectId.Empty))
            .ForMember(dest => dest.Action, opt => opt.MapFrom(src => src.Action))
            .ForMember(dest => dest.Entity, opt => opt.MapFrom(src => src.Entity))
            .ForMember(dest => dest.EntityId, opt => opt.MapFrom(src => src.EntityId.HasValue ? src.EntityId.Value.ToString() : null))
            .ForMember(dest => dest.ReferenceId, opt => opt.MapFrom(src => src.ReferenceId.HasValue ? src.ReferenceId.Value.ToString() : null))
            .ForMember(dest => dest.ChangesJson, opt => opt.MapFrom(src => src.ChangesJson))
            .ForMember(dest => dest.UserName, opt => opt.MapFrom(src => src.UserName))
            .ForMember(dest => dest.IpAddress, opt => opt.MapFrom(src => src.IpAddress))
            .ForMember(dest => dest.UserAgent, opt => opt.MapFrom(src => src.UserAgent))
            .ForMember(dest => dest.CreatedDate, opt => opt.MapFrom(src => src.CreatedDate))
            .ForMember(dest => dest.CreatedById, opt => opt.MapFrom(src => src.CreatedById));
    }
}

