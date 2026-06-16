namespace Project.Gawad.Domain.Objects.Integration;

/// <summary>
/// Medicine export for BHC prescription picker (read-only stock visibility).
/// </summary>
public class GawadMedicineIntegrationDto
{
    public string Id { get; set; } = string.Empty;

    public string Name { get; set; } = string.Empty;

    public string? GenericName { get; set; }

    /// <summary>Enum name, e.g. Tablet, Bottle.</summary>
    public string UnitOfMeasure { get; set; } = string.Empty;

    public decimal CurrentStock { get; set; }

    public int MinimumStockLevel { get; set; }

    public bool IsLowStock { get; set; }

    public bool IsOutOfStock { get; set; }

    public bool IsActive { get; set; } = true;
}
