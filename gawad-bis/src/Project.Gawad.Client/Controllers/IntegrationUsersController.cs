using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Options;
using Project.Gawad.Application.Options;
using Project.Gawad.Core.Providers;

namespace Project.Gawad.Client.Controllers;

[AllowAnonymous]
[Route("api/integration/users")]
public class IntegrationUsersController(
    IUsersProvider usersProvider,
    IOptions<BhcIntegrationOption> bhcIntegrationOption) : ControllerBase
{
    private readonly BhcIntegrationOption _bhcIntegration = bhcIntegrationOption.Value;

    [HttpGet]
    public async Task<IActionResult> List([FromHeader(Name = "X-Integration-Key")] string? integrationKey)
    {
        if (!_bhcIntegration.Enabled)
        {
            return NotFound();
        }

        if (!IsAuthorized(integrationKey))
        {
            return Unauthorized(new { message = "Invalid integration key." });
        }

        var users = await usersProvider.GetStaffIntegrationExportAsync();
        return Ok(users);
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
