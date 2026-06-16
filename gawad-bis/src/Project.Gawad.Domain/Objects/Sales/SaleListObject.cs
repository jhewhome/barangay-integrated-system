using Project.Gawad.Domain.Enums.Sales;

namespace Project.Gawad.Domain.Objects.Sales;

public class SaleListObject
{
    public string Id { get; set; } = string.Empty;

    public DateTime SaleDate { get; set; }

    public SaleStatus Status { get; set; }

    public string StatusName => Status.ToString();

    public string? CustomerName { get; set; }

    public decimal Subtotal { get; set; }

    public decimal DiscountAmount { get; set; }

    public decimal TaxAmount { get; set; }

    public decimal TotalAmount { get; set; }

    public decimal AmountPaid { get; set; }

    public decimal Change { get; set; }

    public int ItemCount { get; set; }

    public string CreatedByName { get; set; } = string.Empty;
}



