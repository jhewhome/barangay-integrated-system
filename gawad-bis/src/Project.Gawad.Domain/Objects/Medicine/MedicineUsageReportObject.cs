namespace Project.Gawad.Domain.Objects.Medicine;

public class MedicineUsageReportObject
{
    public string MedicineName { get; set; } = string.Empty;

    public string Category { get; set; } = string.Empty;

    public decimal TotalQuantityDispensed { get; set; }

    public int NumberOfTransactions { get; set; }

    public decimal? TotalValue { get; set; }

    public DateTime? FirstDispensedDate { get; set; }

    public DateTime? LastDispensedDate { get; set; }

    public string MostCommonRecipient { get; set; } = string.Empty;
}




