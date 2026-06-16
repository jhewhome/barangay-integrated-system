namespace Project.Gawad.Domain.Objects.Medicine;

public class ReportFilterObject
{
    public DateTime? StartDate { get; set; }

    public DateTime? EndDate { get; set; }

    public string? MedicineId { get; set; }

    public string? Category { get; set; }

    public bool? IncludeInactive { get; set; } = false;

    /// <summary>
    /// Filter by the user who created the transaction (for Staff role)
    /// </summary>
    public string? CreatedByUserId { get; set; }
}




