using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Objects.VisitorLog;

namespace Project.Gawad.Client.Controllers;

[AllowAnonymous]
public class VisitorsController(IVisitorLogService visitorLogService, 
    ILogger<VisitorsController> logger) : Controller
{
    private readonly ILogger<VisitorsController> _logger =
        logger ?? throw new ArgumentNullException(nameof(logger));

    private readonly IVisitorLogService _visitorLogService =
        visitorLogService ?? throw new ArgumentNullException(nameof(visitorLogService));

    [HttpGet]
    public IActionResult Index()
    {
        return View(new VisitorLogObject());
    }

    [HttpPost]
    public async Task<IActionResult> Submit(VisitorLogObject visitorLogObject)
    {
        if (!ModelState.IsValid) return View("Index");
        await _visitorLogService.AddAsync(visitorLogObject);

        return RedirectToAction("Index");
    }
}