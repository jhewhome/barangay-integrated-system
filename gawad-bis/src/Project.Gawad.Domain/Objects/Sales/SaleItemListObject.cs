namespace Project.Gawad.Domain.Objects.Sales;

public class SaleItemListObject
{
    public string Id { get; set; } = string.Empty;

    public string MedicineId { get; set; } = string.Empty;

    public string MedicineName { get; set; } = string.Empty;

    public string? MedicineStockId { get; set; }

    public decimal Quantity { get; set; }

    public decimal UnitPrice { get; set; }

    public decimal DiscountAmount { get; set; }

    public decimal LineTotal { get; set; }

    public string? Notes { get; set; }
}



