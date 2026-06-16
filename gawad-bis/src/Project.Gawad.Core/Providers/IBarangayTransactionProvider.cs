using MongoDB.Bson;
using Project.Gawad.Domain.Enums;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Transactions;

namespace Project.Gawad.Core.Providers;

public interface IBarangayTransactionProvider
{
    Task<PaginatedRecords<TransactionListObject>> GetTransactionsListAsync(int page = 1, int itemsPerPage = 10,
        int sortColIndex = 0, string sortColDir = "asc", string? search = null);

    Task<TransactionDetailsObject> GetTransactionDetailObjectAsync(ObjectId transactionId);

    Task<PaginatedRecords<TransactionListObject>> GetResidentTransactionsListAsync(string residentId, int page = 1,
        int itemsPerPage = 10,
        int sortColIndex = 0, string sortColDir = "asc", string? search = null);

    Task<IDictionary<string, string>> GetTransactionDocumentDetailsAsync(ObjectId transactionId);

    Task<BarangayDocumentType> GetDocumetTypeAsync(ObjectId transactionId);

    Task<PaginatedRecords<TransactionListObject>> GetRecentTransactionsListAsync(int lastNDays,
        int page = 1, int itemsPerPage = 10, int sortColIndex = 0,
        string sortColDir = "asc", string? search = null);
}