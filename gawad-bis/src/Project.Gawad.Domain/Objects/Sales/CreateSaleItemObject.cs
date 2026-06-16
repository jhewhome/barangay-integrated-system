namespace Project.Gawad.Domain.Objects.Sales;

public class CreateSaleItemObject
{
    public string MedicineId { get; set; } = string.Empty;

    public string? MedicineStockId { get; set; }

    public decimal Quantity { get; set; }

    public decimal UnitPrice { get; set; }

    public decimal DiscountAmount { get; set; }

    public string? Notes { get; set; }
}



