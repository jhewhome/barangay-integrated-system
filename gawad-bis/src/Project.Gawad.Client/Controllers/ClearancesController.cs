using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Options;
using MongoDB.Bson;
using Project.Gawad.Application.Options;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Objects.Clearance;

namespace Project.Gawad.Client.Controllers;

public class ClearancesController(
    IClearanceService clearanceService,
    IClearanceProvider clearanceProvider,
    ILogger<ClearancesController> logger,
    IOptions<DefaultBarangayAddressOption> defaultBarangayAddress,
    IUsersProvider usersProvider) : Controller
{
    private readonly IClearanceProvider _clearanceProvider =
        clearanceProvider ?? throw new ArgumentNullException(nameof(clearanceProvider));
    
    private readonly IClearanceService _clearanceService =
        clearanceService ?? throw new ArgumentNullException(nameof(clearanceService));

    private readonly DefaultBarangayAddressOption _defaultBarangayAddress =
        defaultBarangayAddress.Value ?? throw new ArgumentNullException(nameof(defaultBarangayAddress));

    private readonly ILogger<ClearancesController> _logger =
        logger ?? throw new ArgumentNullException(nameof(logger));

    private readonly IUsersProvider _usersProvider =
        usersProvider ?? throw new ArgumentNullException(nameof(usersProvider));

    [HttpGet]
    public async Task<IActionResult> ClearanceApplicationForm(string residentId = null)
    {
        var clearanceForm = new ClearanceFormObject();

        if (string.IsNullOrEmpty(residentId))
        {
            clearanceForm = new ClearanceFormObject
            {
                Barangay = _defaultBarangayAddress.Barangay,
                City = _defaultBarangayAddress.City,
                ZipCode = _defaultBarangayAddress.ZipCode,
                Province = _defaultBarangayAddress.Province,
                Country = _defaultBarangayAddress.Country,
                Nationality = "Filipino"
            };
        }
        else
        {
            clearanceForm = await _clearanceProvider.CreateClearanceFormObjectByResidentId(ObjectId.Parse(residentId));
        }
        return View("ClearanceApplicationForm", clearanceForm);
    }

    [HttpPost]
    public async Task<IActionResult> SubmitApplicationForm(ClearanceFormObject clearanceFormObject)
    {
        if (!ModelState.IsValid)
            return View("ClearanceApplicationForm", clearanceFormObject);

        var createdBy = await _usersProvider.GetCurrentUserAsync(HttpContext.User);

        var clearanceResponse = await _clearanceService.ApplyClearanceApplication(clearanceFormObject, createdBy);

        if (!clearanceResponse.IsSuccess)
        {
            foreach (var error in clearanceResponse.ModelState)
                ModelState.AddModelError(error.Key, error.Value);

            return View("ClearanceApplicationForm", clearanceFormObject);
        }

        return RedirectToAction("TransactionDetails", "Transactions",
             new { id = clearanceResponse.Data.TransactionId?.ToString()});
    }
}