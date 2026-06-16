using System.ComponentModel.DataAnnotations.Schema;
using MongoDB.Bson;
using MongoDB.Bson.Serialization.Attributes;
using Project.Gawad.Domain.Enums.Sales;

namespace Project.Gawad.Domain.Entities;

public class Payment : Entity
{
    [ForeignKey(nameof(SaleId))]
    [BsonRepresentation(BsonType.ObjectId)]
    public ObjectId SaleId { get; set; }

    public Sale? Sale { get; set; }

    public PaymentMethod PaymentMethod { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal Amount { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal? Change { get; set; }

    [BsonRepresentation(BsonType.DateTime)]
    public DateTime PaymentDate { get; set; }

    public string? ReferenceNumber { get; set; }

    public string? Notes { get; set; }

    [BsonRepresentation(BsonType.ObjectId)]
    public ObjectId? CashSessionId { get; set; }

    public CashSession? CashSession { get; set; }
}



