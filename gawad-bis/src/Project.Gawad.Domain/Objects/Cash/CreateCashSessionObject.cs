namespace Project.Gawad.Domain.Objects.Cash;

public class CreateCashSessionObject
{
    public DateTime SessionDate { get; set; } = DateTime.Now;

    public decimal OpeningFloat { get; set; }

    public string? Notes { get; set; }
}



