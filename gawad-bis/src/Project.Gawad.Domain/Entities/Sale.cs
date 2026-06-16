using System.ComponentModel.DataAnnotations.Schema;
using MongoDB.Bson;
using MongoDB.Bson.Serialization.Attributes;
using Project.Gawad.Domain.Enums.Sales;

namespace Project.Gawad.Domain.Entities;

public class Sale : Entity
{
    [BsonRepresentation(BsonType.DateTime)]
    public DateTime SaleDate { get; set; }

    public SaleStatus Status { get; set; }

    [BsonRepresentation(BsonType.ObjectId)]
    public ObjectId? CustomerPersonId { get; set; }

    public Person? CustomerPerson { get; set; }

    public string? CustomerName { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal Subtotal { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal DiscountAmount { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal TaxAmount { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal TotalAmount { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal AmountPaid { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal Change { get; set; }

    public string? Notes { get; set; }

    public ICollection<SaleItem> SaleItems { get; set; } = new List<SaleItem>();

    public ICollection<Payment> Payments { get; set; } = new List<Payment>();

    [BsonRepresentation(BsonType.ObjectId)]
    public ObjectId? CashSessionId { get; set; }

    public CashSession? CashSession { get; set; }
}



