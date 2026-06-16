using System.ComponentModel.DataAnnotations;
using MongoDB.Bson;
using Project.Gawad.Domain.Enums.Medicine;

namespace Project.Gawad.Domain.Objects.Medicine;

public class CreateMedicineStockObject
{
    public ObjectId? Id { get; set; }

    [Required]
    [Display(Name = "Medicine")]
    public ObjectId MedicineId { get; set; }

    [Required]
    [Display(Name = "Quantity")]
    [Range(0.01, double.MaxValue, ErrorMessage = "Quantity must be greater than 0")]
    public decimal Quantity { get; set; }

    [Display(Name = "Expiry Date")]
    [DataType(DataType.Date)]
    public DateTime? ExpiryDate { get; set; }

    [Display(Name = "Batch Number")]
    public string? BatchNumber { get; set; }

    [Display(Name = "Lot Number")]
    public string? LotNumber { get; set; }

    [Display(Name = "Cost Per Unit")]
    [DataType(DataType.Currency)]
    public decimal? CostPerUnit { get; set; }

    [Display(Name = "Supplier")]
    public string? Supplier { get; set; }

    [Display(Name = "Received Date")]
    [DataType(DataType.Date)]
    public DateTime? ReceivedDate { get; set; }

    [Display(Name = "Manufacturing Date")]
    [DataType(DataType.Date)]
    public DateTime? ManufacturingDate { get; set; }

    [Display(Name = "Location")]
    public string? Location { get; set; }

    [Display(Name = "Notes")]
    public string? Notes { get; set; }
}




