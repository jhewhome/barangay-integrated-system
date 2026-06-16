using System.ComponentModel.DataAnnotations.Schema;
using MongoDB.Bson;
using MongoDB.Bson.Serialization.Attributes;
using Project.Gawad.Domain.Enums.Cash;

namespace Project.Gawad.Domain.Entities;

public class CashMovement : Entity
{
    [ForeignKey(nameof(CashSessionId))]
    [BsonRepresentation(BsonType.ObjectId)]
    public ObjectId CashSessionId { get; set; }

    public CashSession? CashSession { get; set; }

    public CashMovementType MovementType { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal Amount { get; set; }

    [BsonRepresentation(BsonType.ObjectId)]
    public ObjectId? ReferenceSaleId { get; set; }

    public Sale? ReferenceSale { get; set; }

    [BsonRepresentation(BsonType.ObjectId)]
    public ObjectId? ReferencePaymentId { get; set; }

    public Payment? ReferencePayment { get; set; }

    [BsonRepresentation(BsonType.DateTime)]
    public DateTime MovementDate { get; set; }

    public string? Notes { get; set; }

    public string? Reason { get; set; }
}



