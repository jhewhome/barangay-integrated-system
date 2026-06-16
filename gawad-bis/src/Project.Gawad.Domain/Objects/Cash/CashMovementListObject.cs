using Project.Gawad.Domain.Enums.Cash;

namespace Project.Gawad.Domain.Objects.Cash;

public class CashMovementListObject
{
    public string Id { get; set; } = string.Empty;

    public string CashSessionId { get; set; } = string.Empty;

    public CashMovementType MovementType { get; set; }

    public string MovementTypeName => MovementType.ToString();

    public decimal Amount { get; set; }

    public string? ReferenceSaleId { get; set; }

    public string? ReferencePaymentId { get; set; }

    public DateTime MovementDate { get; set; }

    public string? Notes { get; set; }

    public string? Reason { get; set; }

    public string CreatedByName { get; set; } = string.Empty;
}



