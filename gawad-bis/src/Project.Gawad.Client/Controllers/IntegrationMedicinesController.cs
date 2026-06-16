using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Options;
using Project.Gawad.Application.Options;
using Project.Gawad.Core.Providers;

namespace Project.Gawad.Client.Controllers;

[AllowAnonymous]
[Route("api/integration/medicines")]
public class IntegrationMedicinesController(
    IMedicineProvider medicineProvider,
    IOptions<BhcIntegrationOption> bhcIntegrationOption) : ControllerBase
{
    private readonly BhcIntegrationOption _bhcIntegration = bhcIntegrationOption.Value;

    [HttpGet]
    public async Task<IActionResult> List([FromHeader(Name = "X-Integration-Key")] string? integrationKey)
    {
        if (!_bhcIntegration.Enabled || !_bhcIntegration.MedicineSyncEnabled)
        {
            return NotFound();
        }

        if (!IsAuthorized(integrationKey))
        {
            return Unauthorized(new { message = "Invalid integration key." });
        }

        var medicines = await medicineProvider.GetMedicinesIntegrationExportAsync();
        return Ok(medicines);
    }

    private bool IsAuthorized(string? integrationKey)
    {
        var expected = _bhcIntegration.IntegrationApiKey?.Trim();
        if (string.IsNullOrEmpty(expected))
        {
            return false;
        }

        return string.Equals(expected, integrationKey?.Trim(), StringComparison.Ordinal);
    }
}
