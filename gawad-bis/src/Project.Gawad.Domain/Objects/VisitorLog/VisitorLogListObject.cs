namespace Project.Gawad.Domain.Objects.VisitorLog;

public class VisitorLogListObject
{
    public string Id { get; set; } = string.Empty;
    public string FullName { get; set; } = string.Empty;

    public string Purpose { get; set; } = string.Empty;

    public DateTime? RegistereDateTime { get; set; }
}