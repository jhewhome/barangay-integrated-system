using Project.Gawad.Domain.Enums.Sales;

namespace Project.Gawad.Domain.Objects.Sales;

public class PaymentListObject
{
    public string Id { get; set; } = string.Empty;

    public string SaleId { get; set; } = string.Empty;

    public PaymentMethod PaymentMethod { get; set; }

    public string PaymentMethodName => PaymentMethod.ToString();

    public decimal Amount { get; set; }

    public decimal? Change { get; set; }

    public DateTime PaymentDate { get; set; }

    public string? ReferenceNumber { get; set; }

    public string? Notes { get; set; }
}



