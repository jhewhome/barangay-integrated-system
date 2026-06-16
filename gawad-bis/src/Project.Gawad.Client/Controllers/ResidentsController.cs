using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Options;
using MongoDB.Bson;
using Project.Gawad.Application.Options;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Objects.Resident;

namespace Project.Gawad.Client.Controllers;

public class ResidentsController(
    IResidentsProvider residentsProvider,
    IResidentsService residentsService,
    IOptions<DefaultBarangayAddressOption> defaultBarangayAddress,
    IUsersProvider usersProvider,
    ILogger<ResidentsController> logger) : Controller
{
    private readonly DefaultBarangayAddressOption _defaultBarangayAddress = defaultBarangayAddress.Value;

    private readonly ILogger<ResidentsController> _logger =
        logger ?? throw new ArgumentNullException(nameof(logger));

    private readonly IResidentsProvider _residentsProvider =
        residentsProvider ?? throw new ArgumentNullException(nameof(residentsProvider));

    private readonly IResidentsService _residentsService =
        residentsService ?? throw new ArgumentNullException(nameof(residentsService));

    private readonly IUsersProvider _usersProvider =
        usersProvider ?? throw new ArgumentNullException(nameof(usersProvider));

    [HttpGet]
    public IActionResult Index()
    {
        return View();
    }

    [HttpGet]
    public async Task<IActionResult> Profile(string id)
    {
        if (string.IsNullOrWhiteSpace(id))
            return RedirectToAction(nameof(Index));

        var residentProfile = await _residentsProvider.GetResidentProfileObjectAsync(ObjectId.Parse(id));

        if (residentProfile is null)
            return RedirectToAction(nameof(Index));

        return View(residentProfile);
    }

    [HttpGet]
    public async Task<IActionResult> GetResidentsList(int page = 1, int itemsPerPage = 0, int sortColIndex = 0,
        string sortColDir = "asc", string? search = null)
    {
        var paginatedData =
            await _residentsProvider.GetResidentsListAsync(page, itemsPerPage, sortColIndex, sortColDir, search);
        return Ok(paginatedData);
    }

    [HttpGet]
    public IActionResult EnrollResident()
    {
        var createUpdateResidentObject = new CreateResidentObject
        {
            Nationality = "Filipino",
            PermAddBarangay = _defaultBarangayAddress.Barangay,
            PermAddCity = _defaultBarangayAddress.City,
            PermAddZipCode = _defaultBarangayAddress.ZipCode,
            PermAddProvince = _defaultBarangayAddress.Province,
            PermAddCountry = _defaultBarangayAddress.Country,
            CurrAddBarangay = _defaultBarangayAddress.Barangay,
            CurrAddCity = _defaultBarangayAddress.City,
            CurrAddZipCode = _defaultBarangayAddress.ZipCode,
            CurrAddProvince = _defaultBarangayAddress.Province,
            CurrAddCountry = _defaultBarangayAddress.Country
        };
        return View(createUpdateResidentObject);
    }


    [HttpPost]
    public async Task<IActionResult> EnrollResident(CreateResidentObject createResident)
    {
        // Validate and parse DateOfBirth if it comes as a string
        if (Request.Form.ContainsKey("DateOfBirth"))
        {
            var dateOfBirthStr = Request.Form["DateOfBirth"].ToString();
            if (!string.IsNullOrWhiteSpace(dateOfBirthStr) && DateTime.TryParse(dateOfBirthStr, out var parsedDateOfBirth))
            {
                createResident.DateOfBirth = parsedDateOfBirth;
            }
            else if (!string.IsNullOrWhiteSpace(dateOfBirthStr))
            {
                ModelState.AddModelError("DateOfBirth", $"The value '{dateOfBirthStr}' is not valid for Date of Birth. Please use MM/DD/YYYY format.");
            }
        }

        if (!ModelState.IsValid)
            return View(createResident);

        var createdBy = await _usersProvider.GetCurrentUserAsync(HttpContext.User);

        var result = await _residentsService.CreateResident(createResident, createdBy);

        if (!result.IsSuccess) return View(createResident);

        return RedirectToAction("Profile", new { id = result.Data.Id });
    }

    [HttpGet]
    public async Task<IActionResult> UpdateResident(string id)
    {
        if (string.IsNullOrWhiteSpace(id))
            return RedirectToAction(nameof(Index));

        var updateResident = await _residentsProvider.GetCreateUpdateResidentObjectAsync(ObjectId.Parse(id));

        if (updateResident is null)
            return RedirectToAction(nameof(Index));

        return View(updateResident);
    }


    [HttpPost]
    public async Task<IActionResult> UpdateResident(UpdateResidentObject updateResidentObject)
    {
        // Validate and parse DateOfBirth if it comes as a string
        if (Request.Form.ContainsKey("DateOfBirth"))
        {
            var dateOfBirthStr = Request.Form["DateOfBirth"].ToString();
            if (!string.IsNullOrWhiteSpace(dateOfBirthStr) && DateTime.TryParse(dateOfBirthStr, out var parsedDateOfBirth))
            {
                updateResidentObject.DateOfBirth = parsedDateOfBirth;
            }
            else if (!string.IsNullOrWhiteSpace(dateOfBirthStr))
            {
                ModelState.AddModelError("DateOfBirth", $"The value '{dateOfBirthStr}' is not valid for Date of Birth. Please use MM/DD/YYYY format.");
            }
        }

        if (!ModelState.IsValid)
            return View(updateResidentObject);

        var updatedBy = await _usersProvider.GetCurrentUserAsync(HttpContext.User);

        var result = await _residentsService.UpdateResident(updateResidentObject, updatedBy);

        if (!result.IsSuccess) return View(updateResidentObject);

        return RedirectToAction("Profile", new { id = updateResidentObject.Id });
    }

    [HttpDelete]
    public async Task<IActionResult> DeleteResident(string id)
    {
        if (string.IsNullOrWhiteSpace(id))
            return BadRequest();

        try
        {
            var deletedBy = await _usersProvider.GetCurrentUserAsync(HttpContext.User);

            await _residentsService.RemoveResident(id, deletedBy);
        }
        catch (Exception e)
        {
            _logger.LogError(e, e.Message);
            return StatusCode(500, e.Message);
        }

        return Ok();
    }
}