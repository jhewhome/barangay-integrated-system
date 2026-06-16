namespace Project.Gawad.Domain.Objects.Integration;

/// <summary>
/// Staff user export for BHC account provisioning (SSO username matching).
/// </summary>
public class GawadStaffIntegrationDto
{
    public string UserName { get; set; } = string.Empty;

    public string? FirstName { get; set; }

    public string? LastName { get; set; }

    public string FullName { get; set; } = string.Empty;

    /// <summary>Display name of the Gawad role (e.g. Administrator).</summary>
    public string GawadRole { get; set; } = string.Empty;

    /// <summary>RoleType enum name (e.g. Administrator, Staff).</summary>
    public string GawadRoleType { get; set; } = string.Empty;
}
