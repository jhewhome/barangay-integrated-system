using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Identity;
using Project.Gawad.Domain.Objects.UserManagement;

namespace Project.Gawad.Client.Controllers;

[Authorize(Roles = "Administrator")]
public class UserManagementController(
    IUsersService usersService,
    IUsersProvider usersProvider,
    UserManager<ApplicationUser> userManager,
    RoleManager<ApplicationRole> roleManager,
    ILogger<UserManagementController> logger)
    : Controller
{
    private readonly UserManager<ApplicationUser> _userManager = userManager;
    private readonly RoleManager<ApplicationRole> _roleManager = roleManager;
    private readonly ILogger<UserManagementController> _logger =
        logger ?? throw new ArgumentNullException(nameof(logger));

    private readonly IUsersProvider _usersProvider =
        usersProvider ?? throw new ArgumentNullException(nameof(usersProvider));

    private readonly IUsersService _usersService =
        usersService ?? throw new ArgumentNullException(nameof(usersService));

    [HttpGet]
    public IActionResult Index()
    {
        return View();
    }

    [HttpGet]
    public async Task<IActionResult> GetUsersList(int page = 1, int itemsPerPage = 0, int sortColIndex = 0,
        string sortColDir = "asc", string? search = null)
    {
        var paginatedData =
            await _usersProvider.GetUsersListAsync(page, itemsPerPage, sortColIndex, sortColDir, search);

        return Ok(paginatedData);
    }

    [HttpGet]
    public IActionResult AddUser()
    {
        return View(new CreateUserObject());
    }

    [HttpPost]
    public async Task<IActionResult> AddUser(CreateUserObject createUserObject)
    {
        if (ModelState.IsValid)
        {
            var result = await _usersService.CreateUserAsync(createUserObject);

            if (result.IsSuccess)
            {
                TempData["SuccessMessage"] = result.Message;
                return RedirectToAction(nameof(Index));
            }

            foreach (var error in result.ModelState)
                ModelState.AddModelError(error.Key, error.Value);
        }

        return View(createUserObject);
    }

    [HttpGet]
    public async Task<IActionResult> UpdateUser(string id)
    {
        if (string.IsNullOrEmpty(id))
            return RedirectToAction(nameof(Index));

        var updateUserObject = await _usersProvider.GetUpdateUserObjectByIdAsync(id);

        if (updateUserObject is null)
            return RedirectToAction(nameof(Index));

        return View(updateUserObject);
    }

    [HttpPost]
    public async Task<IActionResult> UpdateUser(UpdateUserObject userObject)
    {
        if (ModelState.IsValid)
        {
            var result = await _usersService.UpdateUserAsync(userObject);
            if (result.IsSuccess)
                return RedirectToAction(nameof(Index));
        }

        return View(userObject);
    }

    [HttpGet]
    public async Task<IActionResult> RolesList()
    {
        return Ok();
    }

    [HttpDelete]
    public async Task<IActionResult> DeleteUser(string id)
    {
        if (string.IsNullOrEmpty(id))
            return BadRequest();

        return NotFound();
    }

    /// <summary>
    /// Fix user role - assigns the specified role to a user
    /// Usage: /UserManagement/FixUserRole?username=BHW&roleName=Health%20Worker%20/%20Staff
    /// </summary>
    [HttpGet]
    public async Task<IActionResult> FixUserRole(string username, string roleName = "Health Worker / Staff")
    {
        if (string.IsNullOrEmpty(username))
            return BadRequest("Username is required");

        // Find the user
        var user = await _userManager.FindByNameAsync(username);
        if (user == null)
            return NotFound($"User '{username}' not found");

        // Check if role exists, if not create it
        if (!await _roleManager.RoleExistsAsync(roleName))
        {
            var newRole = new ApplicationRole
            {
                Name = roleName,
                NormalizedName = roleName.ToUpper()
            };
            await _roleManager.CreateAsync(newRole);
        }

        // Get current roles
        var currentRoles = await _userManager.GetRolesAsync(user);
        
        // Remove from all current roles
        if (currentRoles.Any())
        {
            await _userManager.RemoveFromRolesAsync(user, currentRoles);
        }

        // Add to new role
        var result = await _userManager.AddToRoleAsync(user, roleName);

        if (result.Succeeded)
        {
            return Ok($"User '{username}' has been assigned the role '{roleName}'. Please log out and log back in for changes to take effect.");
        }

        return BadRequest($"Failed to assign role: {string.Join(", ", result.Errors.Select(e => e.Description))}");
    }
}