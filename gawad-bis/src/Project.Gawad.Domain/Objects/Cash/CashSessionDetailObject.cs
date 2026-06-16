using Project.Gawad.Domain.Enums.Cash;

namespace Project.Gawad.Domain.Objects.Cash;

public class CashSessionDetailObject
{
    public string Id { get; set; } = string.Empty;

    public DateTime SessionDate { get; set; }

    public CashSessionStatus Status { get; set; }

    public string StatusName => Status.ToString();

    public string OpenedById { get; set; } = string.Empty;

    public string OpenedByName { get; set; } = string.Empty;

    public DateTime OpenedAt { get; set; }

    public decimal OpeningFloat { get; set; }

    public string? ClosedById { get; set; }

    public string? ClosedByName { get; set; }

    public DateTime? ClosedAt { get; set; }

    public decimal? ClosingAmount { get; set; }

    public decimal? ExpectedAmount { get; set; }

    public decimal? Variance { get; set; }

    public string? ClosingNotes { get; set; }

    public decimal TotalSales { get; set; }

    public decimal TotalPayments { get; set; }

    public int SaleCount { get; set; }

    public List<CashMovementListObject> Movements { get; set; } = new();
}



