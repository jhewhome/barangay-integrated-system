namespace Project.Gawad.Application.Options;

public class BhcIntegrationOption
{
    /// <summary>
    /// When false, Health Center sidebar links are hidden.
    /// </summary>
    public bool Enabled { get; set; } = true;

    /// <summary>
    /// BHC public URL without trailing slash (e.g. http://localhost/bhc_system/public).
    /// </summary>
    public string? BaseUrl { get; set; }

    /// <summary>
    /// Open BHC pages in a new browser tab (recommended — separate app and login).
    /// </summary>
    public bool OpenInNewTab { get; set; } = true;

    /// <summary>
    /// When true, BHC can fetch resident data via the integration API.
    /// </summary>
    public bool ResidentSyncEnabled { get; set; } = true;

    /// <summary>
    /// When true, BHC can fetch active medicines and stock levels via the integration API.
    /// </summary>
    public bool MedicineSyncEnabled { get; set; } = true;

    /// <summary>
    /// Shared secret sent by BHC as X-Integration-Key header. Must match BHC gawad_integration config.
    /// </summary>
    public string? IntegrationApiKey { get; set; }

    /// <summary>
    /// When true, Health Center links include an SSO token for automatic BHC login.
    /// </summary>
    public bool SsoEnabled { get; set; }

    /// <summary>
    /// HMAC secret for SSO tokens. Must match BHC gawad_integration sso_secret.
    /// </summary>
    public string? SsoSecret { get; set; }

    /// <summary>
    /// SSO token lifetime in seconds.
    /// </summary>
    public int SsoTokenLifetimeSeconds { get; set; } = 300;

    public string? BuildUrl(string relativePath)
    {
        if (!Enabled || string.IsNullOrWhiteSpace(BaseUrl))
        {
            return null;
        }

        var baseUrl = BaseUrl.TrimEnd('/');
        var path = relativePath.StartsWith('/') ? relativePath : "/" + relativePath;
        return baseUrl + path;
    }

    public string? BuildRegisterPatientUrl(string residentId)
    {
        if (string.IsNullOrWhiteSpace(residentId))
        {
            return null;
        }

        return BuildUrl("/patients/create?gawad_resident_id=" + Uri.EscapeDataString(residentId));
    }
}
