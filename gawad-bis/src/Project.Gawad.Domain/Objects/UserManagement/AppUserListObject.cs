namespace Project.Gawad.Domain.Objects.UserManagement;

public class AppUserListObject
{
    public required string Id { get; set; }
    public required string FullName { get; set; }

    public required string UserName { get; set; }

    public string Role { get; set; }

    public DateTime? CreatedDateTime { get; set; }

    public DateTime? LastModifiedDate { get; set; }
}