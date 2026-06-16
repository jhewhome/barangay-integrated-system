using System.ComponentModel.DataAnnotations.Schema;
using MongoDB.Bson.Serialization.Attributes;
using MongoDB.Bson;

namespace Project.Gawad.Domain.Entities;

public class MedicineStock : Entity
{
    [ForeignKey(nameof(MedicineId))]
    [BsonRepresentation(BsonType.ObjectId)]
    public ObjectId MedicineId { get; set; }

    public Medicine? Medicine { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal Quantity { get; set; }

    [BsonRepresentation(BsonType.DateTime)]
    public DateTime? ExpiryDate { get; set; }

    public string? BatchNumber { get; set; }

    public string? LotNumber { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal? CostPerUnit { get; set; }

    public string? Supplier { get; set; }

    [BsonRepresentation(BsonType.DateTime)]
    public DateTime? ReceivedDate { get; set; }

    [BsonRepresentation(BsonType.DateTime)]
    public DateTime? ManufacturingDate { get; set; }

    public string? Location { get; set; }

    public string? Notes { get; set; }

    // Expiry notification tracking
    [BsonRepresentation(BsonType.DateTime)]
    public DateTime? NotificationDate { get; set; }

    [BsonRepresentation(BsonType.ObjectId)]
    public ObjectId? NotifiedById { get; set; }

    // Action tracking for expired/expiring medicines
    public string? ActionTaken { get; set; }

    [BsonRepresentation(BsonType.DateTime)]
    public DateTime? ActionDate { get; set; }

    [BsonRepresentation(BsonType.ObjectId)]
    public ObjectId? ActionTakenById { get; set; }

    public string? ActionNotes { get; set; }
}




