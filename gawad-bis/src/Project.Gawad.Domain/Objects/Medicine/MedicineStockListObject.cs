using MongoDB.Bson;

namespace Project.Gawad.Domain.Objects.Medicine;

public class MedicineStockListObject
{
    public ObjectId Id { get; set; }

    public string MedicineName { get; set; } = string.Empty;

    public decimal Quantity { get; set; }

    public DateTime? ExpiryDate { get; set; }

    public string? BatchNumber { get; set; }

    public string? LotNumber { get; set; }

    public string? Location { get; set; }

    public bool IsExpiringSoon { get; set; }

    public bool IsExpired { get; set; }

    // Notification tracking
    public DateTime? NotificationDate { get; set; }

    public bool IsNotified { get; set; }

    // Action tracking
    public string? ActionTaken { get; set; }

    public DateTime? ActionDate { get; set; }

    public string? ActionNotes { get; set; }

    public bool HasAction { get; set; }

    // Calculated days until expiry
    public int? DaysUntilExpiry { get; set; }

    // Calculated days since expiry
    public int? DaysSinceExpiry { get; set; }
}




