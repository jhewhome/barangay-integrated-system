using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Options;
using MongoDB.Bson;
using Project.Gawad.Application.Options;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Objects.BusinessPermit;

namespace Project.Gawad.Client.Controllers;

public class BusinessPermitsController(
    IBusinessPermitService businessPermitService,
    IBusinessPermitProvider businessPermitProvider,
    IOptions<DefaultBarangayAddressOption> defaultBarangayAddress,
    IUsersProvider usersProvider,
    ILogger<BusinessPermitsController> logger) : Controller
{
    private readonly IBusinessPermitService _businessPermitService =
        businessPermitService ?? throw new ArgumentNullException(nameof(businessPermitService));
    
    private readonly IBusinessPermitProvider _businessPermitProvider =
        businessPermitProvider ?? throw new ArgumentNullException(nameof(businessPermitProvider));

    private readonly DefaultBarangayAddressOption _defaultBarangayAddress =
        defaultBarangayAddress.Value ?? throw new ArgumentNullException(nameof(defaultBarangayAddress));

    private readonly ILogger<BusinessPermitsController> _logger =
        logger ?? throw new ArgumentNullException(nameof(logger));

    private readonly IUsersProvider _usersProvider =
        usersProvider ?? throw new ArgumentNullException(nameof(usersProvider));

    [HttpGet]
    public async Task<IActionResult> ApplyBusinessPermit(string residentId = null)
    {
        var businessPermit = new BusinessPermitFormObject();

        if (string.IsNullOrEmpty(residentId))
        {
            businessPermit = new BusinessPermitFormObject
            {
                Barangay = _defaultBarangayAddress.Barangay,
                City = _defaultBarangayAddress.City,
                ZipCode = _defaultBarangayAddress.ZipCode,
                Province = _defaultBarangayAddress.Province,
                Country = _defaultBarangayAddress.Country,
                Nationality = "Filipino",
                BussBarangay = _defaultBarangayAddress.Barangay,
                BussCity = _defaultBarangayAddress.City,
                BussZipCode = _defaultBarangayAddress.ZipCode,
                BussProvince = _defaultBarangayAddress.Province,
                BussCountry = _defaultBarangayAddress.Country
            };
        }
        else
        {
            businessPermit = await _businessPermitProvider.CreateBusinessPermitFormObjectByResidentId(ObjectId.Parse(residentId));
        }

        return View(businessPermit);
    }

    [HttpPost]
    public async Task<IActionResult> ApplyBusinessPermit(BusinessPermitFormObject businessPermit)
    {
        if (!ModelState.IsValid)
            return View(businessPermit);

        var createdBy = await _usersProvider.GetCurrentUserAsync(HttpContext.User);

        var businessPermitResponse = await _businessPermitService.ApplyBusinessPermit(businessPermit, createdBy);

        if (!businessPermitResponse.IsSuccess)
        {
            foreach (var error in businessPermitResponse.ModelState)
                ModelState.AddModelError(error.Key, error.Value);

            return View("ApplyBusinessPermit", businessPermit);
        }

        return RedirectToAction("TransactionDetails", "Transactions",
            routeValues: new { id = businessPermitResponse.Data.TransactionId?.ToString() });
    }
}