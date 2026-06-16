namespace Project.Gawad.Core.Services;

public interface IBhcSsoLinkService
{
    string? BuildLink(string relativePath, string? username, string? role = null);

    string? BuildRegisterPatientLink(string residentId, string? username, string? role = null);
}
