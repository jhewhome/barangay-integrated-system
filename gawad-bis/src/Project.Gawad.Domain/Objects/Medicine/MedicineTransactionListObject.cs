using MongoDB.Bson;
using Project.Gawad.Domain.Enums.Medicine;

namespace Project.Gawad.Domain.Objects.Medicine;

public class MedicineTransactionListObject
{
    public ObjectId Id { get; set; }

    public string MedicineName { get; set; } = string.Empty;

    public MedicineTransactionType TransactionType { get; set; }

    public string TransactionTypeName { get; set; } = string.Empty;

    public decimal Quantity { get; set; }

    public DateTime TransactionDate { get; set; }

    public string? RecipientName { get; set; }

    public string? Reason { get; set; }

    public decimal? UnitPrice { get; set; }

    public decimal? TotalAmount { get; set; }

    public string? Notes { get; set; }

    /// <summary>
    /// The user who created this transaction (Admin, Health Worker, etc.)
    /// </summary>
    public string? CreatedByName { get; set; }

    /// <summary>
    /// The role of the user who created this transaction
    /// </summary>
    public string? CreatedByRole { get; set; }

    /// <summary>
    /// When the transaction was created
    /// </summary>
    public DateTime? CreatedDate { get; set; }
}

