using System.Diagnostics;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Project.Gawad.Client.Models;
using Project.Gawad.Core.Providers;

namespace Project.Gawad.Client.Controllers;

public class HomeController(
    IDashboardProvider dashboardProvider,
    ILogger<HomeController> logger) : Controller
{
    private readonly IDashboardProvider _dashboardProvider =
        dashboardProvider ?? throw new ArgumentNullException(nameof(dashboardProvider));

    private readonly ILogger<HomeController> _logger =
        logger ?? throw new ArgumentNullException(nameof(logger));

    public IActionResult Index()
    {
        return View();
    }

    [ResponseCache(Duration = 0, Location = ResponseCacheLocation.None, NoStore = true)]
    public IActionResult Error()
    {
        return View(new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
    }

    [HttpGet]
    public async Task<IActionResult> GetDashboardStas()
    {
        var stats = await _dashboardProvider.GetDashboardStats();
        return Ok(stats);
    }

    [HttpGet]
    [AllowAnonymous]
    public async Task<IActionResult> GetTodayBirthdays()
    {
        try
        {
            var today = DateTime.Today;
            Console.WriteLine($"Getting birthdays for date: {today:yyyy-MM-dd}");
            
            var birthdays = await _dashboardProvider.GetTodayBirthdays(today);
            Console.WriteLine($"Found {birthdays?.Data?.Count ?? 0} birthdays");
            
            return Ok(birthdays);
        }
        catch (Exception ex)
        {
            Console.WriteLine($"Error getting today's birthdays: {ex.Message}");
            Console.WriteLine($"Stack trace: {ex.StackTrace}");
            
            // Return empty data instead of error to prevent DataTables from showing error
            var emptyResponse = new
            {
                data = new List<object>(),
                recordsTotal = 0,
                recordsFiltered = 0,
                pageNumber = 1,
                pageSize = 0
            };
            
            return Ok(emptyResponse);
        }
    }

    [HttpGet]
    public IActionResult UnderConstruction()
    {
        return View("~/Views/Shared/UnderConstruction.cshtml");
    }
}