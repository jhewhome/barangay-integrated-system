using MongoDB.Bson;

namespace Project.Gawad.Domain.Objects.Authentication;

public class ApplicationUserObject
{
    public ObjectId Id { get; set; }

    public string? UserName { get; set; }

    public string? FirstName { get; set; }

    public string? LastName { get; set; }

    public string? Role { get; set; }

    public string? FullName { get; set; }
}