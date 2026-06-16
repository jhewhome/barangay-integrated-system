namespace Project.Gawad.Domain.Objects.Integration;

/// <summary>
/// Resident export payload for BHC patient registration (Phase 2 integration).
/// </summary>
public class GawadResidentIntegrationDto
{
    public string Id { get; set; } = string.Empty;

    public string FirstName { get; set; } = string.Empty;

    public string? MiddleName { get; set; }

    public string LastName { get; set; } = string.Empty;

    public string? Suffix { get; set; }

    public string FullName { get; set; } = string.Empty;

    /// <summary>M or F</summary>
    public string Sex { get; set; } = string.Empty;

    /// <summary>yyyy-MM-dd</summary>
    public string? Birthdate { get; set; }

    public string? ContactNumber { get; set; }

    public string? Address { get; set; }

    public string? Barangay { get; set; }

    /// <summary>BHC civil_status: single, married, widowed, separated</summary>
    public string? CivilStatus { get; set; }

    public bool IsBarangayResident { get; set; }
}
