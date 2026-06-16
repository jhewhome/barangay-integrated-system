using System.Security.Claims;
using Microsoft.AspNetCore.Authentication;
using Microsoft.AspNetCore.Authentication.Cookies;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Options;
using Project.Gawad.Application.Options;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Objects.UserManagement;
using Project.Gawad.Domain.ViewModels.Login;

namespace Project.Gawad.Client.Controllers;

[AllowAnonymous]
public class AccountsController(
    IUsersService usersService,
    IUsersProvider usersProvider,
    ILogger<AccountsController> logger,
    IOptions<CookieConfigOption> cookieConfigOption)
    : Controller
{
    private readonly CookieConfigOption _cookieConfigOption =
        cookieConfigOption?.Value ?? throw new ArgumentNullException(nameof(cookieConfigOption));

    private readonly ILogger<AccountsController> _logger = 
        logger ?? throw new ArgumentNullException(nameof(logger));

    private readonly IUsersProvider _usersProvider =
        usersProvider ?? throw new ArgumentNullException(nameof(usersProvider));

    private readonly IUsersService _usersService =
        usersService ?? throw new ArgumentNullException(nameof(usersService));

    [HttpGet]
    public ActionResult Index()
    {
        if (User.Identity!.IsAuthenticated)
            return RedirectToAction("Index", "Home");

        LoginViewModel loginViewModel = new();

        return View(loginViewModel);
    }

    [HttpPost]
    public async Task<ActionResult> SignIn([FromForm] LoginViewModel loginViewModel)
    {
        if (!ModelState.IsValid)
            return View("Index", loginViewModel);

        var user = await _usersService.SignInAsync(loginViewModel.Username,
            loginViewModel.Password, loginViewModel.KeepMeLogin);

        if (user is null)
        {
            ModelState.AddModelError("Username", "Invalid username or password.");
            return RedirectToAction("Index", "Accounts");
        }

        var claimsIdentity = new ClaimsIdentity(CookieAuthenticationDefaults.AuthenticationScheme,
            ClaimTypes.Name, ClaimTypes.Role);

        claimsIdentity.AddClaim(new Claim(ClaimTypes.NameIdentifier, user.Id.ToString()));
        claimsIdentity.AddClaim(new Claim(ClaimTypes.Name, user.UserName));
        claimsIdentity.AddClaim(new Claim(ClaimTypes.UserData, $"{user.FirstName} {user.LastName}"));
        claimsIdentity.AddClaim(new Claim(ClaimTypes.GivenName, user.FirstName));
        claimsIdentity.AddClaim(new Claim(ClaimTypes.Surname, user.LastName));
        claimsIdentity.AddClaim(new Claim(ClaimTypes.Role, user.Role));

        await HttpContext.SignInAsync(
            CookieAuthenticationDefaults.AuthenticationScheme,
            new ClaimsPrincipal(claimsIdentity),
            new AuthenticationProperties
            {
                AllowRefresh = true,
                IsPersistent = loginViewModel.KeepMeLogin,
                IssuedUtc = DateTime.UtcNow,
                ExpiresUtc = DateTime.UtcNow.AddHours(_cookieConfigOption.ExpirationHours)
            });

        return RedirectToAction("Index", "Home");
    }

    [HttpGet]
    public new async Task<ActionResult> SignOut()
    {
        await _usersService.SignOutAsync();

        await HttpContext.SignOutAsync(
            CookieAuthenticationDefaults.AuthenticationScheme);

        return RedirectToAction("Index", "Accounts");
    }

    [HttpGet]
    public IActionResult ChangePassword()
    {
        return View();
    }

    [HttpPost]
    public async Task<IActionResult> ChangePassword(ChangePasswordObject changePasswordObject)
    {
        if (ModelState.IsValid)
        {
            var currentUser = await _usersProvider.GetCurrentUserAsync(HttpContext.User);
            var result = await _usersService.ChangePassword(changePasswordObject, currentUser);

            if (result.IsSuccess)
                return RedirectToAction(nameof(Index));

            ModelState.AddModelError("NewPassword", result.ModelState.FirstOrDefault().Value);
            changePasswordObject.ConfirmNewPassword = string.Empty;
        }

        return View(changePasswordObject);
    }
}