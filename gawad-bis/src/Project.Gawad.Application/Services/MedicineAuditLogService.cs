using Microsoft.Extensions.Logging;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Authentication;
using System.Text.Json;

namespace Project.Gawad.Application.Services;

public class MedicineAuditLogService(
    IMedicineAuditLogRepository auditLogRepository,
    ILogger<MedicineAuditLogService> logger) : IMedicineAuditLogService
{
    private readonly IMedicineAuditLogRepository _auditLogRepository =
        auditLogRepository ?? throw new ArgumentNullException(nameof(auditLogRepository));

    private readonly ILogger<MedicineAuditLogService> _logger =
        logger ?? throw new ArgumentNullException(nameof(logger));

    public async Task LogActionAsync(
        string action,
        string entity,
        ObjectId? entityId,
        ObjectId? referenceId,
        string? changesJson,
        ApplicationUserObject user,
        string? ipAddress = null,
        string? userAgent = null)
    {
        try
        {
            var auditLog = new MedicineAuditLog
            {
                Action = action,
                Entity = entity,
                EntityId = entityId,
                ReferenceId = referenceId,
                ChangesJson = changesJson,
                UserName = user.UserName ?? "Unknown",
                IpAddress = ipAddress,
                UserAgent = userAgent,
                CreatedDate = DateTime.UtcNow,
                CreatedById = user.Id,
                IsDeleted = false
            };

            await _auditLogRepository.AddAsync(auditLog);
            await _auditLogRepository.SaveChangesAsync();
        }
        catch (Exception ex)
        {
            // Log error but don't throw - audit logging should not break the main operation
            _logger.LogError(ex, "Failed to log audit action: {Action} for entity: {Entity}", action, entity);
        }
    }
}

