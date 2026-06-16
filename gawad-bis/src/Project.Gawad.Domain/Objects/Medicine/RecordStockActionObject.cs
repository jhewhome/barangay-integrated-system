using System.ComponentModel.DataAnnotations;
using MongoDB.Bson;

namespace Project.Gawad.Domain.Objects.Medicine;

public class RecordStockActionObject
{
    [Required]
    public ObjectId StockId { get; set; }

    [Required]
    [Display(Name = "Action Taken")]
    public string ActionTaken { get; set; } = string.Empty;

    [Display(Name = "Action Notes")]
    public string? ActionNotes { get; set; }
}







