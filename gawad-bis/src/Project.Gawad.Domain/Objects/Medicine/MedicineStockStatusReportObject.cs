namespace Project.Gawad.Domain.Objects.Medicine;

public class MedicineStockStatusReportObject
{
    public string MedicineName { get; set; } = string.Empty;

    public string Category { get; set; } = string.Empty;

    public decimal CurrentStock { get; set; }

    public int MinimumStockLevel { get; set; }

    public bool IsLowStock { get; set; }

    public int ExpiringSoonCount { get; set; }

    public int ExpiredCount { get; set; }

    public string Status { get; set; } = string.Empty;

    public decimal? UnitPrice { get; set; }

    public decimal? TotalValue { get; set; }

    public string? Supplier { get; set; }
}

