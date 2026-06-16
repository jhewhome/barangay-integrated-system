using MongoDB.Bson;

namespace Project.Gawad.Domain.Objects.Medicine;

public class MedicineSpendingLogObject
{
    public ObjectId? StockId { get; set; }
    public string MedicineName { get; set; } = string.Empty;
    public string? Supplier { get; set; }
    public string? BatchNumber { get; set; }
    public string? LotNumber { get; set; }
    public decimal Quantity { get; set; }
    public decimal? UnitCost { get; set; }
    public decimal? TotalCost { get; set; }
    public DateTime? ReceivedDate { get; set; }
    public DateTime? ExpiryDate { get; set; }
}





