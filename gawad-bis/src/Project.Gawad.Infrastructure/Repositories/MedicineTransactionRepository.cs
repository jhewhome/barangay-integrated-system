using Microsoft.EntityFrameworkCore;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums.Medicine;

namespace Project.Gawad.Infrastructure.Repositories;

public class MedicineTransactionRepository(GawadMongoDbContext dbContext)
    : BaseRepository<MedicineTransaction>(dbContext), IMedicineTransactionRepository
{
    public async Task<IEnumerable<MedicineTransaction>> GetTransactionsByMedicineIdAsync(ObjectId medicineId)
    {
        return await _dbContext.Set<MedicineTransaction>()
            .AsNoTracking()
            .Where(x => x.MedicineId == medicineId && !x.IsDeleted)
            .OrderByDescending(x => x.TransactionDate)
            .ToListAsync();
    }

    public async Task<IEnumerable<MedicineTransaction>> GetTransactionsByTypeAsync(MedicineTransactionType transactionType)
    {
        return await _dbContext.Set<MedicineTransaction>()
            .AsNoTracking()
            .Where(x => x.TransactionType == transactionType && !x.IsDeleted)
            .OrderByDescending(x => x.TransactionDate)
            .ToListAsync();
    }

    public async Task<IEnumerable<MedicineTransaction>> GetTransactionsByDateRangeAsync(DateTime startDate, DateTime endDate)
    {
        return await _dbContext.Set<MedicineTransaction>()
            .AsNoTracking()
            .Where(x => x.TransactionDate >= startDate && 
                       x.TransactionDate <= endDate && 
                       !x.IsDeleted)
            .OrderByDescending(x => x.TransactionDate)
            .ToListAsync();
    }

    public async Task<IEnumerable<MedicineTransaction>> GetTransactionsByRecipientAsync(ObjectId recipientPersonId)
    {
        return await _dbContext.Set<MedicineTransaction>()
            .AsNoTracking()
            .Where(x => x.RecipientPersonId == recipientPersonId && !x.IsDeleted)
            .OrderByDescending(x => x.TransactionDate)
            .ToListAsync();
    }

    public async Task<IEnumerable<MedicineTransaction>> GetDispensedTransactionsAsync(
        ObjectId medicineId, 
        ObjectId recipientPersonId, 
        DateTime startDate, 
        DateTime endDate)
    {
        return await _dbContext.Set<MedicineTransaction>()
            .AsNoTracking()
            .Where(x => x.MedicineId == medicineId &&
                       x.RecipientPersonId == recipientPersonId &&
                       x.TransactionType == MedicineTransactionType.Dispensed &&
                       x.TransactionDate >= startDate &&
                       x.TransactionDate <= endDate &&
                       !x.IsDeleted)
            .OrderBy(x => x.TransactionDate)
            .ToListAsync();
    }

    public async Task<bool> DeleteTransactionsByMedicineIdAsync(ObjectId medicineId, ObjectId deletedById)
    {
        var transactions = await _dbContext.Set<MedicineTransaction>()
            .Where(x => x.MedicineId == medicineId && !x.IsDeleted)
            .ToListAsync();

        if (!transactions.Any())
            return true;

        foreach (var transaction in transactions)
        {
            transaction.IsDeleted = true;
            transaction.LastModifiedDate = DateTime.Now;
            transaction.LastModifiedById = deletedById;
        }

        await _dbContext.SaveChangesAsync();
        return true;
    }
}




