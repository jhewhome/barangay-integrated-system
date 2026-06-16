namespace Project.Gawad.Domain.Objects.Sales;

public class CreateSaleObject
{
    public DateTime SaleDate { get; set; } = DateTime.Now;

    public string? CustomerPersonId { get; set; }

    public string? CustomerName { get; set; }

    public List<CreateSaleItemObject> Items { get; set; } = new();

    public decimal DiscountAmount { get; set; }

    public decimal TaxAmount { get; set; }

    public string? Notes { get; set; }
}



