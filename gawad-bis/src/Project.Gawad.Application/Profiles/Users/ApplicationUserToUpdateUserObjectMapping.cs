using AutoMapper;
using Project.Gawad.Domain.Identity;
using Project.Gawad.Domain.Objects.UserManagement;

namespace Project.Gawad.Application.Profiles.Users;

public class ApplicationUserToUpdateUserObjectMapping : Profile
{
    public ApplicationUserToUpdateUserObjectMapping()
    {
        CreateMap<ApplicationUser, UpdateUserObject>()
            .ForMember(dst => dst.Id,
                opt => opt.MapFrom(src => src.Id))
            .ForMember(dst => dst.Firstname,
                opt => opt.MapFrom(src => src.FirstName))
            .ForMember(dst => dst.Lastname,
                opt => opt.MapFrom(src => src.LastName))
            .ForMember(dst => dst.Username,
                opt => opt.MapFrom(src => src.UserName))
            .ForMember(dst => dst.Role,
                opt => opt.MapFrom(src => src.RoleType))
            ;
    }
}