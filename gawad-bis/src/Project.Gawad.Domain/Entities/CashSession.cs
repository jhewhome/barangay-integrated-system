using System.ComponentModel.DataAnnotations.Schema;
using MongoDB.Bson;
using MongoDB.Bson.Serialization.Attributes;
using Project.Gawad.Domain.Enums.Cash;
using Project.Gawad.Domain.Identity;

namespace Project.Gawad.Domain.Entities;

public class CashSession : Entity
{
    [BsonRepresentation(BsonType.DateTime)]
    public DateTime SessionDate { get; set; }

    public CashSessionStatus Status { get; set; }

    public string OpenedById { get; set; } = string.Empty;

    public ApplicationUser? OpenedBy { get; set; }

    [BsonRepresentation(BsonType.DateTime)]
    public DateTime OpenedAt { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal OpeningFloat { get; set; }

    public string? ClosedById { get; set; }

    public ApplicationUser? ClosedBy { get; set; }

    [BsonRepresentation(BsonType.DateTime)]
    public DateTime? ClosedAt { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal? ClosingAmount { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal? ExpectedAmount { get; set; }

    [BsonRepresentation(BsonType.Decimal128)]
    public decimal? Variance { get; set; }

    public string? ClosingNotes { get; set; }

    public ICollection<Sale> Sales { get; set; } = new List<Sale>();

    public ICollection<Payment> Payments { get; set; } = new List<Payment>();

    public ICollection<CashMovement> CashMovements { get; set; } = new List<CashMovement>();
}
