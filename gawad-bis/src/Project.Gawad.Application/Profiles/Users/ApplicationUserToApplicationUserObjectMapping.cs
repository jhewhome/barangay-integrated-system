using AutoMapper;
using Project.Gawad.Core.Extensions;
using Project.Gawad.Domain.Identity;
using Project.Gawad.Domain.Objects.Authentication;

namespace Project.Gawad.Application.Profiles.Users;

public class ApplicationUserToApplicationUserObjectMapping : Profile
{
    public ApplicationUserToApplicationUserObjectMapping()
    {
        CreateMap<ApplicationUser, ApplicationUserObject>()
            .ForMember(dst => dst.Id,
                opt => opt.MapFrom(src => src.Id))
            .ForMember(dst => dst.FirstName,
                opt => opt.MapFrom(src => src.FirstName))
            .ForMember(dst => dst.LastName,
                opt => opt.MapFrom(src => src.LastName))
            .ForMember(dst => dst.UserName,
                opt => opt.MapFrom(src => src.UserName))
            .ForMember(dst => dst.FullName,
                opt => opt.MapFrom(src => $"{src.FirstName}  {src.LastName}"))
            .ForMember(dst => dst.Role,
                opt => opt.MapFrom(src => src.RoleType.GetEnumDisplayName()))
            ;
    }
}