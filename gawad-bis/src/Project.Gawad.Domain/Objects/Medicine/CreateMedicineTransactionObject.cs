using System.ComponentModel.DataAnnotations;
using MongoDB.Bson;
using Project.Gawad.Domain.Enums.Medicine;

namespace Project.Gawad.Domain.Objects.Medicine;

public class CreateMedicineTransactionObject
{
    public ObjectId? Id { get; set; }

    [Required]
    [Display(Name = "Medicine")]
    public ObjectId MedicineId { get; set; }

    [Display(Name = "Stock Batch")]
    public ObjectId? MedicineStockId { get; set; }

    [Required]
    [Display(Name = "Transaction Type")]
    public MedicineTransactionType TransactionType { get; set; }

    [Required]
    [Display(Name = "Quantity")]
    [Range(0.01, double.MaxValue, ErrorMessage = "Quantity must be greater than 0")]
    public decimal Quantity { get; set; }

    [Required]
    [Display(Name = "Transaction Date")]
    [DataType(DataType.DateTime)]
    public DateTime TransactionDate { get; set; } = DateTime.Now;

    [Display(Name = "Recipient (Resident)")]
    public ObjectId? RecipientPersonId { get; set; }

    [Display(Name = "Recipient Name")]
    public string? RecipientName { get; set; }

    [Display(Name = "Prescription Number/Details")]
    public string? Prescription { get; set; }

    [Display(Name = "Reason")]
    public string? Reason { get; set; }

    [Display(Name = "Notes")]
    public string? Notes { get; set; }

    [Display(Name = "Unit Price")]
    [DataType(DataType.Currency)]
    public decimal? UnitPrice { get; set; }
}




