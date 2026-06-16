using AutoMapper;
using MongoDB.Bson;
using Project.Gawad.Domain.Identity;
using Project.Gawad.Domain.Objects.UserManagement;

namespace Project.Gawad.Application.Profiles.Users;

public class CreateUserToApplicationUserObjectMapping : Profile
{
    public CreateUserToApplicationUserObjectMapping()
    {
        CreateMap<CreateUserObject, ApplicationUser>()
            .ForMember(dst => dst.Id,
                opt => opt.MapFrom(src => ObjectId.GenerateNewId()))
            .ForMember(dst => dst.FirstName,
                opt => opt.MapFrom(src => src.Firstname))
            .ForMember(dst => dst.LastName,
                opt => opt.MapFrom(src => src.Lastname))
            .ForMember(dst => dst.UserName,
                opt => opt.MapFrom(src => src.Username))
            .ForMember(dst => dst.RoleType,
                opt => opt.MapFrom(src => src.Role))
            .ForMember(dst => dst.CreatedDateTime,
                opt => opt.MapFrom(src => DateTime.UtcNow));
        ;
    }
}