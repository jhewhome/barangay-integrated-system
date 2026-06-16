using Microsoft.AspNetCore.Mvc;
using Project.Gawad.Core.Providers;

namespace Project.Gawad.Client.Controllers;

public class VisitorsLogController(
    IVisitorLogsProvider visitorLogsProvider,
    ILogger<VisitorsLogController> logger) : Controller
{
    private readonly ILogger<VisitorsLogController> _logger =
        logger ?? throw new ArgumentNullException(nameof(logger));

    private readonly IVisitorLogsProvider _visitorLogsProvider =
        visitorLogsProvider ?? throw new ArgumentNullException(nameof(visitorLogsProvider));

    [HttpGet]
    public IActionResult Index()
    {
        return View();
    }

    [HttpGet]
    public async Task<IActionResult> GetVisitorsList(string residentId, int page = 1, int itemsPerPage = 0,
        int sortColIndex = 0,
        string sortColDir = "asc", string? search = null)
    {
        var paginatedData =
            await _visitorLogsProvider.GetVisitorListAsync(page, itemsPerPage, sortColIndex, sortColDir, search);
        return Ok(paginatedData);
    }
}