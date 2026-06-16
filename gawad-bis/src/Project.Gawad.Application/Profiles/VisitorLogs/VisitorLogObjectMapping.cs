using AutoMapper;
using MongoDB.Bson;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.VisitorLog;

namespace Project.Gawad.Application.Profiles.VisitorLogs;

public class VisitorLogObjectMapping : Profile
{
    public VisitorLogObjectMapping()
    {
        CreateMap<VisitorLogObject, VisitorLog>()
            .ForMember(dst => dst.Id, src
                => src.MapFrom(src => ObjectId.GenerateNewId()))
            .ForMember(dst => dst.CreatedDate, src
                => src.MapFrom(src => DateTime.UtcNow));
    }
}