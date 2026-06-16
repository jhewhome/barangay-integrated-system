using AutoMapper;
using Project.Gawad.Domain.Identity;
using Project.Gawad.Domain.Objects.UserManagement;

namespace Project.Gawad.Application.Profiles.Users;

public class UpdateUserToApplicationUserObjectMapping : Profile
{
    public UpdateUserToApplicationUserObjectMapping()
    {
        CreateMap<UpdateUserObject, ApplicationUser>()
            .ForMember(dst => dst.Id,
                opt => opt.MapFrom(src => src.Id))
            .ForMember(dst => dst.FirstName,
                opt => opt.MapFrom(src => src.Firstname))
            .ForMember(dst => dst.LastName,
                opt => opt.MapFrom(src => src.Lastname))
            .ForMember(dst => dst.UserName,
                opt => opt.Ignore())
            .ForMember(dst => dst.RoleType,
                opt => opt.MapFrom(src => src.Role))
            .ForMember(dst => dst.LastModifiedDate,
                opt => opt.MapFrom(src => DateTime.UtcNow))
            ;
    }
}