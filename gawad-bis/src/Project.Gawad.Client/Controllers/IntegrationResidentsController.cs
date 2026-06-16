using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Options;
using MongoDB.Bson;
using Project.Gawad.Application.Options;
using Project.Gawad.Core.Providers;

namespace Project.Gawad.Client.Controllers;

[AllowAnonymous]
[Route("api/integration/residents")]
public class IntegrationResidentsController(
    IResidentsProvider residentsProvider,
    IOptions<BhcIntegrationOption> bhcIntegrationOption) : ControllerBase
{
    private readonly BhcIntegrationOption _bhcIntegration = bhcIntegrationOption.Value;

    [HttpGet("{id}")]
    public async Task<IActionResult> Get(
        string id,
        [FromHeader(Name = "X-Integration-Key")] string? integrationKey)
    {
        if (!_bhcIntegration.ResidentSyncEnabled)
        {
            return NotFound();
        }

        if (!IsAuthorized(integrationKey))
        {
            return Unauthorized(new { message = "Invalid integration key." });
        }

        if (!ObjectId.TryParse(id, out var residentId))
        {
            return NotFound(new { message = "Resident not found." });
        }

        var resident = await residentsProvider.GetResidentIntegrationExportAsync(residentId);
        if (resident is null)
        {
            return NotFound(new { message = "Resident not found." });
        }

        return Ok(resident);
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
