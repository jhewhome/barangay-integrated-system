namespace Project.Gawad.Domain.Objects.Medicine;

public class AuditLogFilterObject
{
    public DateTime? StartDate { get; set; }

    public DateTime? EndDate { get; set; }

    public string? Action { get; set; } // Create, Update, Delete, StockIn, Dispensed, Adjust, Discard, ReportExport

    public string? Entity { get; set; } // Medicine, MedicineStock, MedicineTransaction, Report

    public string? EntityId { get; set; }

    public string? UserId { get; set; }

    public string? UserName { get; set; }
}






