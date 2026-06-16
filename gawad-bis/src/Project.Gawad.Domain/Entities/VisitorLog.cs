namespace Project.Gawad.Domain.Entities;

public class VisitorLog : Entity
{
    public string FirstName { get; set; } = string.Empty;

    public string? MiddleName { get; set; }

    public string LastName { get; set; } = string.Empty;

    public string Purpose { get; set; } = string.Empty;
}