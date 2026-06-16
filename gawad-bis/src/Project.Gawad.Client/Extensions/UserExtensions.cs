using System.Security.Claims;
using MongoDB.Bson;
using Project.Gawad.Domain.Objects.Authentication;

namespace Project.Gawad.Client.Extensions;

public static class UserExtensions
{
    public static string GetClaimValue(this ClaimsPrincipal principal, string claimName)
    {
        return principal.Claims.FirstOrDefault(claim => claim.Type == claimName)?.Value;
    }

    public static ApplicationUserObject GetUserObject(this ClaimsPrincipal principal)
    {
        var id = principal.FindFirst(ClaimTypes.NameIdentifier)?.Value;
        var role = principal.FindFirst(ClaimTypes.Role)?.Value;
        var userName = principal.FindFirst(ClaimTypes.Name)?.Value;
        var fullName = principal.FindFirst("UserFullName")?.Value;
        var firstName = principal.FindFirst("UserFirstname")?.Value;
        var lastName = principal.FindFirst("UserLastname")?.Value;

        return new ApplicationUserObject
        {
            Id = ObjectId.Parse(id),
            Role = role,
            UserName = userName,
            FullName = fullName,
            FirstName = firstName,
            LastName = lastName
        };
    }
}