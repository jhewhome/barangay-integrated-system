using Project.Gawad.Domain.Enums.Sales;

namespace Project.Gawad.Domain.Objects.Sales;

public class SaleDetailObject
{
    public string Id { get; set; } = string.Empty;

    public DateTime SaleDate { get; set; }

    public SaleStatus Status { get; set; }

    public string StatusName => Status.ToString();

    public string? CustomerPersonId { get; set; }

    public string? CustomerName { get; set; }

    public decimal Subtotal { get; set; }

    public decimal DiscountAmount { get; set; }

    public decimal TaxAmount { get; set; }

    public decimal TotalAmount { get; set; }

    public decimal AmountPaid { get; set; }

    public decimal Change { get; set; }

    public string? Notes { get; set; }

    public List<SaleItemListObject> Items { get; set; } = new();

    public List<PaymentListObject> Payments { get; set; } = new();

    public string CreatedByName { get; set; } = string.Empty;

    public DateTime CreatedDate { get; set; }
}



