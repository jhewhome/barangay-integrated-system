using AutoMapper;
using Microsoft.AspNetCore.Identity;
using Project.Gawad.Core.Extensions;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Identity;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.UserManagement;

namespace Project.Gawad.Application.Services;

public class UsersService(
    UserManager<ApplicationUser> userManager,
    SignInManager<ApplicationUser> signInManager,
    IMapper mapper)
    : IUsersService
{
    public const string DefaultPassword = "P@ssw0rd123!";
    private readonly IMapper _mapper =
        mapper ?? throw new ArgumentNullException(nameof(mapper));

    private readonly SignInManager<ApplicationUser> _signInManager =
        signInManager ?? throw new ArgumentNullException(nameof(signInManager));

    private readonly UserManager<ApplicationUser> _userManager =
        userManager ?? throw new ArgumentNullException(nameof(userManager));

    public async Task<ApplicationUserObject?> SignInAsync(string userName, string password, bool isPersistent = false)
    {
        var result = await _signInManager.PasswordSignInAsync(userName, password,
            isPersistent, false);

        if (result.Succeeded)
        {
            var applicationUser = await _userManager.FindByNameAsync(userName);
            if (applicationUser is null || applicationUser.IsDeleted)
                return null;

            var credential = new ApplicationUserObject();
            _mapper.Map(applicationUser, credential);
            return credential;
        }

        return null;
    }

    public async Task<bool> SignOutAsync()
    {
        try
        {
            await _signInManager.SignOutAsync();
        }
        catch (Exception e)
        {
            return false;
        }

        return true;
    }


    public async Task<ServiceResponse<ApplicationUser?>> CreateUserAsync(CreateUserObject createUserObject)
    {
        var response = new ServiceResponse<ApplicationUser?>();
        var applicationUser = _mapper.Map<CreateUserObject, ApplicationUser>(createUserObject);
        var password = string.IsNullOrWhiteSpace(createUserObject.Password)
            ? DefaultPassword
            : createUserObject.Password.Trim();

        var createResult = await _userManager.CreateAsync(applicationUser, password);
        if (!createResult.Succeeded)
        {
            foreach (var error in createResult.Errors)
                response.AddModelError(error.Code, error.Description);

            return response;
        }

        var roleName = createUserObject.Role.GetEnumDisplayName();
        var roleResult = await _userManager.AddToRoleAsync(applicationUser, roleName);
        if (!roleResult.Succeeded)
        {
            await _userManager.DeleteAsync(applicationUser);
            foreach (var error in roleResult.Errors)
                response.AddModelError(error.Code, error.Description);

            return response;
        }

        response.Data = applicationUser;
        response.Message =
            $"User \"{applicationUser.UserName}\" was created. Initial password: {password}. " +
            "Ask the user to change it under Profile → Change Password. " +
            "For Health Center (BHC) sign-in, import this username in BHC → Staff accounts.";

        return response;
    }

    public async Task<ServiceResponse<ApplicationUser?>> UpdateUserAsync(UpdateUserObject updateUserObject)
    {
        var applicationUser = await _userManager.FindByIdAsync(updateUserObject.Id.ToString());

        applicationUser = _mapper.Map<UpdateUserObject, ApplicationUser>(updateUserObject, applicationUser);

        await _userManager.UpdateAsync(applicationUser);
        await _userManager.RemoveFromRoleAsync(applicationUser, applicationUser.RoleType.GetEnumDisplayName());
        await userManager.AddToRoleAsync(applicationUser, updateUserObject.Role.GetEnumDisplayName());

        return new ServiceResponse<ApplicationUser?>(applicationUser);
    }

    public async Task<ServiceResponse<ApplicationUser?>> ChangePassword(
        ChangePasswordObject changePasswordObject,
        ApplicationUserObject applicationUserObject)
    {
        var applicationUser = await _userManager.FindByIdAsync(applicationUserObject.Id.ToString());

        if (applicationUser is null)
            throw new ApplicationException("Application user not found");

        var result = await _userManager.ChangePasswordAsync(applicationUser, changePasswordObject.CurrentPassword,
            changePasswordObject.NewPassword);

        if (!result.Succeeded)
        {
            Dictionary<string, string> changePasswordErrors = new Dictionary<string, string>();
            foreach (var error in result.Errors) changePasswordErrors.Add(error.Code, error.Description);

            return new ServiceResponse<ApplicationUser?>(applicationUser)
            {
                ModelState = changePasswordErrors
            };
        }

        return new ServiceResponse<ApplicationUser?>(applicationUser);
    }

    public async Task<ServiceResponse<ApplicationUser?>> ResetPassword(string id,
        ApplicationUserObject applicationUserObject)
    {
        var applicationUser = await _userManager.FindByIdAsync(applicationUserObject.Id.ToString());

        if (applicationUser is null)
            throw new ApplicationException("Application user not found");

        await _userManager.ResetPasswordAsync(applicationUser, id, DefaultPassword);

        return new ServiceResponse<ApplicationUser?>(applicationUser);
    }
}