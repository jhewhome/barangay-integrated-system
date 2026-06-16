namespace Project.Gawad.Domain.Objects.Dashboard;

public class BirthdayResidentListObject
{
    public string Id { get; set; } = string.Empty;
    public string FullName { get; set; } = string.Empty;
    public DateTime DateOfBirth { get; set; }
    public int Age { get; set; }
}