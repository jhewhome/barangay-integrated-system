using System.Linq;
using MongoDB.Bson;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Core.Repositories;

public interface IMedicineAuditLogRepository : IBaseRepository<MedicineAuditLog>
{
    Task<IEnumerable<MedicineAuditLog>> GetAuditLogsByEntityIdAsync(ObjectId entityId);
    
    Task<IEnumerable<MedicineAuditLog>> GetAuditLogsByActionAsync(string action);
    
    Task<IEnumerable<MedicineAuditLog>> GetAuditLogsByUserAsync(ObjectId userId);
    
    Task<IEnumerable<MedicineAuditLog>> GetAuditLogsByDateRangeAsync(DateTime startDate, DateTime endDate);
    
    IQueryable<MedicineAuditLog> GetQueryable();
}

