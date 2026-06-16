using System.ComponentModel.DataAnnotations.Schema;
using MongoDB.Bson.Serialization.Attributes;
using MongoDB.Bson;
using Project.Gawad.Domain.Enums.Medicine;

namespace Project.Gawad.Domain.Entities;

public class MedicineTransaction : Entity
{
    [ForeignKey(nameof(MedicineId))]
    [BsonRepresentation(BsonType.ObjectId)]
    public ObjectId MedicineId { get; set; }

    public Medicine? Medicine { get; set; }

    [ForeignKey(nameof(MedicineStockId))]
    [BsonRepresentation(BsonType.ObjectId)]
    public ObjectId? MedicineStockId { get; set; }

    public MedicineStock? MedicineStock { get; set; }

    public MedicineTransactionType TransactionType { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal Quantity { get; set; }

    [BsonRepresentation(BsonType.DateTime)]
    public DateTime TransactionDate { get; set; }

    [BsonRepresentation(BsonType.ObjectId)]
    public ObjectId? RecipientPersonId { get; set; }

    public Person? RecipientPerson { get; set; }

    public string? RecipientName { get; set; }

    public string? Reason { get; set; }

    public string? Notes { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal? UnitPrice { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal? TotalAmount { get; set; }
}




