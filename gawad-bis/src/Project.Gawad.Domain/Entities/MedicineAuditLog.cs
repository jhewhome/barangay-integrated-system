using MongoDB.Bson;
using MongoDB.Bson.Serialization.Attributes;

namespace Project.Gawad.Domain.Entities;

public class MedicineAuditLog : Entity
{
    public string Action { get; set; } = string.Empty; // Create, Update, Delete, StockIn, Dispensed, Adjust, Discard, ReportExport

    public string Entity { get; set; } = string.Empty; // Medicine, MedicineStock, MedicineTransaction, Report

    [BsonRepresentation(BsonType.ObjectId)]
    public ObjectId? EntityId { get; set; }

    [BsonRepresentation(BsonType.ObjectId)]
    public ObjectId? ReferenceId { get; set; }

    public string? ChangesJson { get; set; }

    public string? UserName { get; set; }

    public string? IpAddress { get; set; }

    public string? UserAgent { get; set; }
}





