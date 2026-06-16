using MongoDB.Bson;
using Project.Gawad.Domain.Enums.Medicine;

namespace Project.Gawad.Domain.Objects.Medicine;

public class MedicineDetailObject
{
    public ObjectId Id { get; set; }

    public string Name { get; set; } = string.Empty;

    public string? Description { get; set; }

    public string? Dosage { get; set; }

    public DosageType? DosageType { get; set; }

    public MedicineCategory Category { get; set; }

    public string CategoryName { get; set; } = string.Empty;

    public UnitOfMeasure UnitOfMeasure { get; set; }

    public string UnitOfMeasureName { get; set; } = string.Empty;

    public string? Manufacturer { get; set; }

    public string? GenericName { get; set; }

    public decimal? UnitPrice { get; set; }

    public int MinimumStockLevel { get; set; }

    public bool IsPrescriptionRequired { get; set; }

    public bool IsActive { get; set; }

    public string? Notes { get; set; }

    public decimal TotalStock { get; set; }

    public int ExpiringSoonCount { get; set; }

    public int ExpiredCount { get; set; }

    // Allocation/Limit information
    public bool? IsLimitedSupply { get; set; }

    public AllocationPeriod? AllocationPeriod { get; set; }

    public decimal? MaxQuantityPerPeriod { get; set; }

    public bool IsLowStock { get; set; }

    public string? BottleMeasurementType { get; set; } // "mg", "ml", or null

    public decimal? BottleMeasurementValue { get; set; } // e.g., 100, 200
}




