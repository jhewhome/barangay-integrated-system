using Project.Gawad.Domain.Identity;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.UserManagement;

namespace Project.Gawad.Core.Services;

public interface IUsersService
{
    Task<ApplicationUserObject?> SignInAsync(string userName, string password, bool isPersistent = false);

    Task<bool> SignOutAsync();

    Task<ServiceResponse<ApplicationUser?>> CreateUserAsync(CreateUserObject createUserObject);

    Task<ServiceResponse<ApplicationUser?>> UpdateUserAsync(UpdateUserObject updateUserObject);

    Task<ServiceResponse<ApplicationUser?>> ChangePassword(ChangePasswordObject changePasswordObject,
        ApplicationUserObject applicationUserObject);
}