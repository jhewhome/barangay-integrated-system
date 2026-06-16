using MongoDB.Bson;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Core.Repositories;

public interface IMedicineStockRepository : IBaseRepository<MedicineStock>
{
    Task<IEnumerable<MedicineStock>> GetStocksByMedicineIdAsync(ObjectId medicineId);
    
    Task<decimal> GetTotalStockByMedicineIdAsync(ObjectId medicineId);
    
    Task<IEnumerable<MedicineStock>> GetExpiringStocksAsync(DateTime? beforeDate = null);
    
    Task<IEnumerable<MedicineStock>> GetExpiredStocksAsync();
    
    Task<IEnumerable<MedicineStock>> GetLowStockItemsAsync();
}




