using System.ComponentModel.DataAnnotations;
using MongoDB.Bson;
using Project.Gawad.Domain.Enums.Medicine;

namespace Project.Gawad.Domain.Objects.Medicine;

public class UpdateMedicineTransactionObject
{
    [Required]
    public ObjectId TransactionId { get; set; }

    [Required]
    [Display(Name = "Quantity")]
    [Range(0.01, double.MaxValue, ErrorMessage = "Quantity must be greater than 0")]
    public decimal Quantity { get; set; }

    [Required]
    [Display(Name = "Transaction Date")]
    [DataType(DataType.DateTime)]
    public DateTime TransactionDate { get; set; }

    [Display(Name = "Unit Price")]
    [DataType(DataType.Currency)]
    public decimal? UnitPrice { get; set; }

    [Display(Name = "Reason")]
    public string? Reason { get; set; }

    [Display(Name = "Notes")]
    public string? Notes { get; set; }
}







