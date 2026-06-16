using Microsoft.AspNetCore.Mvc;
using MongoDB.Bson;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.Sales;

namespace Project.Gawad.Client.Controllers;

public class SalesController(
    ISalesProvider salesProvider,
    ISalesService salesService,
    IMedicineProvider medicineProvider,
    IUsersProvider usersProvider,
    ILogger<SalesController> logger) : Controller
{
    private readonly ILogger<SalesController> _logger = logger ?? throw new ArgumentNullException(nameof(logger));
    private readonly ISalesProvider _salesProvider = salesProvider ?? throw new ArgumentNullException(nameof(salesProvider));
    private readonly ISalesService _salesService = salesService ?? throw new ArgumentNullException(nameof(salesService));
    private readonly IMedicineProvider _medicineProvider = medicineProvider ?? throw new ArgumentNullException(nameof(medicineProvider));
    private readonly IUsersProvider _usersProvider = usersProvider ?? throw new ArgumentNullException(nameof(usersProvider));

    [HttpGet]
    public async Task<IActionResult> Index(int page = 1, DateTime? startDate = null, DateTime? endDate = null)
    {
        var sales = await _salesProvider.GetSalesListAsync(page, 20, startDate, endDate);
        return View(sales);
    }

    [HttpGet]
    public async Task<IActionResult> Create()
    {
        var model = new CreateSaleObject
        {
            SaleDate = DateTime.Now,
            Items = new List<CreateSaleItemObject>()
        };
        return View(model);
    }

    [HttpPost]
    public async Task<IActionResult> Create(CreateSaleObject createSaleObject)
    {
        if (!ModelState.IsValid)
            return View(createSaleObject);

        var createdBy = await _usersProvider.GetCurrentUserAsync(HttpContext.User);

        var result = await _salesService.CreateSale(createSaleObject, createdBy);

        if (!result.IsSuccess)
        {
            foreach (var error in result.ModelState)
                ModelState.AddModelError(error.Key, error.Value);

            return View(createSaleObject);
        }

        // Redirect to sale details if ID is available in the message
        if (!string.IsNullOrWhiteSpace(result.Message))
            return RedirectToAction("Details", new { id = result.Message });

        return RedirectToAction(nameof(Index));
    }

    [HttpGet]
    public async Task<IActionResult> Details(string id)
    {
        if (string.IsNullOrWhiteSpace(id))
            return RedirectToAction(nameof(Index));

        var saleDetail = await _salesProvider.GetSaleDetailObjectAsync(ObjectId.Parse(id));

        if (saleDetail is null)
            return RedirectToAction(nameof(Index));

        return View(saleDetail);
    }

    [HttpGet]
    public async Task<IActionResult> Payment(string saleId)
    {
        if (string.IsNullOrWhiteSpace(saleId))
            return RedirectToAction(nameof(Index));

        var saleDetail = await _salesProvider.GetSaleDetailObjectAsync(ObjectId.Parse(saleId));
        if (saleDetail == null)
            return RedirectToAction(nameof(Index));

        var paymentObject = new CreatePaymentObject
        {
            SaleId = saleId,
            PaymentMethod = Domain.Enums.Sales.PaymentMethod.Cash,
            Amount = saleDetail.TotalAmount - saleDetail.AmountPaid
        };

        return View(paymentObject);
    }

    [HttpPost]
    public async Task<IActionResult> Payment(CreatePaymentObject createPaymentObject)
    {
        if (!ModelState.IsValid)
            return View(createPaymentObject);

        var createdBy = await _usersProvider.GetCurrentUserAsync(HttpContext.User);

        var result = await _salesService.ProcessPayment(createPaymentObject, createdBy);

        if (!result.IsSuccess)
        {
            foreach (var error in result.ModelState)
                ModelState.AddModelError(error.Key, error.Value);

            return View(createPaymentObject);
        }

        return RedirectToAction("Details", new { id = createPaymentObject.SaleId });
    }

    [HttpGet]
    public async Task<IActionResult> Return(string saleId)
    {
        if (string.IsNullOrWhiteSpace(saleId))
            return RedirectToAction(nameof(Index));

        // Placeholder: load sale detail for display; implement selection of items to return
        var saleDetail = await _salesProvider.GetSaleDetailObjectAsync(ObjectId.Parse(saleId));
        if (saleDetail == null)
            return RedirectToAction(nameof(Index));

        ViewBag.SaleDetail = saleDetail;
        return View();
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public IActionResult Return()
    {
        // Placeholder: handle return submission in next phase
        TempData["Info"] = "Return processing will be implemented.";
        return RedirectToAction(nameof(Index));
    }

    [HttpGet]
    public async Task<IActionResult> Exchange(string saleId)
    {
        if (string.IsNullOrWhiteSpace(saleId))
            return RedirectToAction(nameof(Index));

        // Placeholder: load sale detail; implement selection of item to exchange and replacement
        var saleDetail = await _salesProvider.GetSaleDetailObjectAsync(ObjectId.Parse(saleId));
        if (saleDetail == null)
            return RedirectToAction(nameof(Index));

        ViewBag.SaleDetail = saleDetail;
        return View();
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public IActionResult Exchange()
    {
        // Placeholder: handle exchange submission in next phase
        TempData["Info"] = "Exchange processing will be implemented.";
        return RedirectToAction(nameof(Index));
    }
}
