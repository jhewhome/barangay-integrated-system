namespace Project.Gawad.Domain.Objects.Cash;

public class CloseCashSessionObject
{
    public string SessionId { get; set; } = string.Empty;

    public decimal ClosingAmount { get; set; }

    public string? ClosingNotes { get; set; }
}



