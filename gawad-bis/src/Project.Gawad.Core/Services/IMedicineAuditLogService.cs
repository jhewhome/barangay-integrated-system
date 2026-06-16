using MongoDB.Bson;
using Project.Gawad.Domain.Objects.Authentication;

namespace Project.Gawad.Core.Services;

public interface IMedicineAuditLogService
{
    /// <summary>
    /// Log a user action for audit purposes
    /// </summary>
    Task LogActionAsync(
        string action,
        string entity,
        ObjectId? entityId,
        ObjectId? referenceId,
        string? changesJson,
        ApplicationUserObject user,
        string? ipAddress = null,
        string? userAgent = null);
}






