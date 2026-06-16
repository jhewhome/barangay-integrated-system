using MongoDB.Bson;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums.Medicine;

namespace Project.Gawad.Core.Repositories;

public interface IMedicineTransactionRepository : IBaseRepository<MedicineTransaction>
{
    Task<IEnumerable<MedicineTransaction>> GetTransactionsByMedicineIdAsync(ObjectId medicineId);
    
    Task<IEnumerable<MedicineTransaction>> GetTransactionsByTypeAsync(MedicineTransactionType transactionType);
    
    Task<IEnumerable<MedicineTransaction>> GetTransactionsByDateRangeAsync(DateTime startDate, DateTime endDate);
    
    Task<IEnumerable<MedicineTransaction>> GetTransactionsByRecipientAsync(ObjectId recipientPersonId);
    
    Task<IEnumerable<MedicineTransaction>> GetDispensedTransactionsAsync(
        ObjectId medicineId, 
        ObjectId recipientPersonId, 
        DateTime startDate, 
        DateTime endDate);
    
    Task<bool> DeleteTransactionsByMedicineIdAsync(ObjectId medicineId, ObjectId deletedById);
}




