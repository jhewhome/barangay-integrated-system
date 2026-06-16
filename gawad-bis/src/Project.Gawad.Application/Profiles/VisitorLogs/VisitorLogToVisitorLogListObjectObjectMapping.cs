using AutoMapper;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.VisitorLog;

namespace Project.Gawad.Application.Profiles.VisitorLogs;

public class VisitorLogToVisitorLogListObjectObjectMapping : Profile
{
    public VisitorLogToVisitorLogListObjectObjectMapping()
    {
        CreateMap<VisitorLog, VisitorLogListObject>()
            .ForMember(dst => dst.Id, src
                => src.MapFrom(src => src.Id.ToString()))
            .ForMember(dst => dst.FullName, src
                => src.MapFrom(src => $"{src.FirstName} {src.LastName}"))
            .ForMember(dst => dst.Purpose, src
                => src.MapFrom(src => src.Purpose))
            .ForMember(dst => dst.RegistereDateTime, src
                => src.MapFrom(src => src.CreatedDate))
            ;
    }
}