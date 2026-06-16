using MongoDB.Bson;
using Project.Gawad.Domain.Enums.Transactions;

namespace Project.Gawad.Domain.Entities;

public class BarangayTrasaction : Entity
{
    public TransactionType Type { get; set; }

    public string ControlNumber { get; set; }

    public double Fee { get; set; }

    public ObjectId PersonId { get; set; }

    public ObjectId? ResidentId { get; set; }

    public string? Notes { get; set; }

    public string? ReceiptNumber { get; set; }

    public string? OfficerOfTheDay { get; set; }

    public ObjectId? OfficerOfTheDayId { get; set; }
}