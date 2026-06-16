using AutoMapper;
using Project.Gawad.Domain.Identity;
using Project.Gawad.Domain.Objects.UserManagement;

namespace Project.Gawad.Application.Profiles.ApplicationUsers;

public class AppUserListObjectMapping : Profile
{
    public AppUserListObjectMapping()
    {
        CreateMap<ApplicationUser, AppUserListObject>()
            .ForMember(dst => dst.Id,
                src => src.MapFrom(x => x.Id.ToString()))
            .ForMember(dst => dst.FullName,
                src => src.MapFrom(x => $"{x.FirstName} {x.LastName}"))
            .ForMember(dst => dst.UserName,
                src => src.MapFrom(x => x.UserName))
            .ForMember(dst => dst.CreatedDateTime,
                src => src.MapFrom(x => x.CreatedDateTime))
            .ForMember(dst => dst.LastModifiedDate,
                src => src.MapFrom(x => x.LastModifiedDate))
            ;
    }
}