using Microsoft.AspNetCore.Mvc;
using MongoDB.Bson;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.Cash;

namespace Project.Gawad.Client.Controllers;

public class CashController(
    ICashProvider cashProvider,
    ICashService cashService,
    IUsersProvider usersProvider,
    ILogger<CashController> logger) : Controller
{
    private readonly ILogger<CashController> _logger = logger ?? throw new ArgumentNullException(nameof(logger));
    private readonly ICashProvider _cashProvider = cashProvider ?? throw new ArgumentNullException(nameof(cashProvider));
    private readonly ICashService _cashService = cashService ?? throw new ArgumentNullException(nameof(cashService));
    private readonly IUsersProvider _usersProvider = usersProvider ?? throw new ArgumentNullException(nameof(usersProvider));

    [HttpGet]
    public async Task<IActionResult> Index(int page = 1)
    {
        var sessions = await _cashProvider.GetCashSessionsListAsync(page, 20);
        return View(sessions);
    }

    [HttpGet]
    public IActionResult Open()
    {
        var model = new CreateCashSessionObject
        {
            SessionDate = DateTime.Now,
            OpeningFloat = 0
        };
        return View(model);
    }

    [HttpPost]
    public async Task<IActionResult> Open(CreateCashSessionObject createSessionObject)
    {
        if (!ModelState.IsValid)
            return View(createSessionObject);

        var openedBy = await _usersProvider.GetCurrentUserAsync(HttpContext.User);

        var result = await _cashService.OpenCashSession(createSessionObject, openedBy);

        if (!result.IsSuccess)
        {
            foreach (var error in result.ModelState)
                ModelState.AddModelError(error.Key, error.Value);

            return View(createSessionObject);
        }

        return RedirectToAction(nameof(Dashboard));
    }

    [HttpGet]
    public async Task<IActionResult> Dashboard()
    {
        var openSession = await _cashProvider.GetOpenCashSessionAsync();
        if (openSession == null)
            return RedirectToAction(nameof(Open));

        return View(openSession);
    }

    [HttpGet]
    public async Task<IActionResult> Close(string id)
    {
        if (string.IsNullOrWhiteSpace(id))
            return RedirectToAction(nameof(Dashboard));

        var session = await _cashProvider.GetCashSessionDetailObjectAsync(ObjectId.Parse(id));
        if (session == null || session.Status == Domain.Enums.Cash.CashSessionStatus.Closed)
            return RedirectToAction(nameof(Dashboard));

        var closeObject = new CloseCashSessionObject
        {
            SessionId = id,
            ClosingAmount = session.ExpectedAmount ?? session.OpeningFloat
        };

        return View(closeObject);
    }

    [HttpPost]
    public async Task<IActionResult> Close(CloseCashSessionObject closeSessionObject)
    {
        if (!ModelState.IsValid)
            return View(closeSessionObject);

        var closedBy = await _usersProvider.GetCurrentUserAsync(HttpContext.User);

        var result = await _cashService.CloseCashSession(closeSessionObject, closedBy);

        if (!result.IsSuccess)
        {
            foreach (var error in result.ModelState)
                ModelState.AddModelError(error.Key, error.Value);

            return View(closeSessionObject);
        }

        return RedirectToAction(nameof(Index));
    }

    [HttpGet]
    public async Task<IActionResult> Details(string id)
    {
        if (string.IsNullOrWhiteSpace(id))
            return RedirectToAction(nameof(Index));

        var sessionDetail = await _cashProvider.GetCashSessionDetailObjectAsync(ObjectId.Parse(id));

        if (sessionDetail is null)
            return RedirectToAction(nameof(Index));

        return View(sessionDetail);
    }
}



