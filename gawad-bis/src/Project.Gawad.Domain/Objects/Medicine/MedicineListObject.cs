namespace Project.Gawad.Domain.Objects.Medicine;

public class MedicineListObject
{
    public string Id { get; set; } = string.Empty;

    public string Name { get; set; } = string.Empty;

    public string Category { get; set; } = string.Empty;

    public string UnitOfMeasure { get; set; } = string.Empty;

    public decimal CurrentStock { get; set; }

    public int MinimumStockLevel { get; set; }

    public bool IsLowStock { get; set; }

    public bool IsActive { get; set; }

    public string? Supplier { get; set; }

    public decimal TotalDispensed { get; set; }

    public decimal? UnitPrice { get; set; }

    public string? BottleMeasurementType { get; set; }

    public decimal? BottleMeasurementValue { get; set; }

    /// <summary>
    /// Count of stock batches expiring within 30 days
    /// </summary>
    public int ExpiringSoonCount { get; set; }

    /// <summary>
    /// Indicates if the medicine is out of stock (CurrentStock = 0)
    /// </summary>
    public bool IsOutOfStock => CurrentStock <= 0;
}

