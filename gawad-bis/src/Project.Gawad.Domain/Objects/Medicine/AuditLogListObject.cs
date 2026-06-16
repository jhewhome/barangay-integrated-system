using MongoDB.Bson;

namespace Project.Gawad.Domain.Objects.Medicine;

public class AuditLogListObject
{
    public ObjectId Id { get; set; }

    public string Action { get; set; } = string.Empty;

    public string Entity { get; set; } = string.Empty;

    public string? EntityId { get; set; }

    public string? ReferenceId { get; set; }

    public string? ChangesJson { get; set; }

    public string? UserName { get; set; }

    public string? UserRole { get; set; }

    public string? IpAddress { get; set; }

    public string? UserAgent { get; set; }

    public DateTime CreatedDate { get; set; }

    public ObjectId? CreatedById { get; set; }
}






