using System.ComponentModel.DataAnnotations;
using MongoDB.Bson;
using Project.Gawad.Domain.Enums.Medicine;

namespace Project.Gawad.Domain.Objects.Medicine;

public class CreateMedicineObject
{
    public ObjectId? Id { get; set; }

    [Required]
    [Display(Name = "Medicine Name")]
    public string Name { get; set; } = string.Empty;

    [Display(Name = "Description")]
    public string? Description { get; set; }

    [Display(Name = "Dosage")]
    public string? Dosage { get; set; }

    [Display(Name = "Type of Dosage")]
    public DosageType? DosageType { get; set; }

    [Required]
    [Display(Name = "Category")]
    public MedicineCategory Category { get; set; }

    [Required]
    [Display(Name = "Unit of Measure")]
    public UnitOfMeasure UnitOfMeasure { get; set; }

    [Display(Name = "Manufacturer")]
    public string? Manufacturer { get; set; }

    [Display(Name = "Generic Name")]
    public string? GenericName { get; set; }

    [Display(Name = "Unit Price")]
    [DataType(DataType.Currency)]
    public decimal? UnitPrice { get; set; }

    [Required]
    [Display(Name = "Minimum Stock Level")]
    [Range(0, int.MaxValue, ErrorMessage = "Minimum stock level must be 0 or greater")]
    public int MinimumStockLevel { get; set; } = 0;

    [Display(Name = "Prescription Required")]
    public bool IsPrescriptionRequired { get; set; } = false;

    [Display(Name = "Active")]
    public bool IsActive { get; set; } = true;

    [Display(Name = "Notes")]
    public string? Notes { get; set; }

    [Display(Name = "Bottle Measurement Type")]
    public string? BottleMeasurementType { get; set; } // "mg", "ml", or null

    [Display(Name = "Bottle Measurement Value")]
    public decimal? BottleMeasurementValue { get; set; } // e.g., 100, 200

    [Display(Name = "Box Content Type")]
    public string? BoxContentType { get; set; } // "tablet", "capsule", "solution", "cream", or null

    [Display(Name = "Box Content Value")]
    public decimal? BoxContentValue { get; set; } // e.g., number of pieces per box
}




