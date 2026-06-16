using MongoDB.Bson.Serialization.Attributes;
using MongoDB.Bson;
using Project.Gawad.Domain.Enums.Medicine;

namespace Project.Gawad.Domain.Entities;

public class Medicine : Entity
{
    public string Name { get; set; } = string.Empty;

    public string? Description { get; set; }

    public string? Dosage { get; set; }

    public DosageType? DosageType { get; set; }

    public MedicineCategory Category { get; set; }

    public UnitOfMeasure UnitOfMeasure { get; set; }

    public string? Manufacturer { get; set; }

    public string? GenericName { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal? UnitPrice { get; set; }

    public int MinimumStockLevel { get; set; } = 0;

    public bool IsPrescriptionRequired { get; set; } = false;

    public bool IsActive { get; set; } = true;

    public string? Notes { get; set; }

    // Allocation/Rationing for limited supply medicines
    public bool? IsLimitedSupply { get; set; }

    public AllocationPeriod? AllocationPeriod { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal? MaxQuantityPerPeriod { get; set; }

    // Bottle measurement (for bottle-type medicines)
    public string? BottleMeasurementType { get; set; } // "mg", "ml", or null

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal? BottleMeasurementValue { get; set; } // e.g., 100, 200

    // Box content type (for box-type medicines)
    public string? BoxContentType { get; set; } // "tablet", "capsule", "solution", "cream", or null

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal? BoxContentValue { get; set; } // e.g., number of pieces per box
}




