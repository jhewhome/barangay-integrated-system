using Project.Gawad.Domain.Enums.Sales;

namespace Project.Gawad.Domain.Objects.Sales;

public class CreatePaymentObject
{
    public string SaleId { get; set; } = string.Empty;

    public PaymentMethod PaymentMethod { get; set; }

    public decimal Amount { get; set; }

    public decimal? Change { get; set; }

    public string? ReferenceNumber { get; set; }

    public string? Notes { get; set; }
}



