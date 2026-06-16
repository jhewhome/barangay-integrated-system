using AutoMapper;
using Project.Gawad.Core.Extensions;
using Project.Gawad.Domain.Identity;
using Project.Gawad.Domain.Objects.Authentication;

namespace Project.Gawad.Application.Profiles.ApplicationUsers;

public class ApplicationUserMapping : Profile
{
    public ApplicationUserMapping()
    {
        CreateMap<ApplicationUser, ApplicationUserObject>()
            .ForMember(dst => dst.Role,
                opt => opt.MapFrom(src => src.RoleType.GetEnumDisplayName()))
            .ForMember(dst => dst.FullName,
                opt => opt.MapFrom(src => $"{src.FirstName} {src.LastName}"));
        ;
    }
}