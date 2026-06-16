using System.ComponentModel.DataAnnotations.Schema;
using MongoDB.Bson;
using MongoDB.Bson.Serialization.Attributes;

namespace Project.Gawad.Domain.Entities;

public class SaleItem : Entity
{
    [ForeignKey(nameof(SaleId))]
    [BsonRepresentation(BsonType.ObjectId)]
    public ObjectId SaleId { get; set; }

    public Sale? Sale { get; set; }

    [ForeignKey(nameof(MedicineId))]
    [BsonRepresentation(BsonType.ObjectId)]
    public ObjectId MedicineId { get; set; }

    public Medicine? Medicine { get; set; }

    [ForeignKey(nameof(MedicineStockId))]
    [BsonRepresentation(BsonType.ObjectId)]
    public ObjectId? MedicineStockId { get; set; }

    public MedicineStock? MedicineStock { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal Quantity { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal UnitPrice { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal DiscountAmount { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal LineTotal { get; set; }

    public string? Notes { get; set; }
}



