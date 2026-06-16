using MongoDB.Bson;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Cash;

namespace Project.Gawad.Core.Providers;

public interface ICashProvider
{
    Task<CashSessionDetailObject?> GetOpenCashSessionAsync();

    Task<CashSessionDetailObject?> GetCashSessionDetailObjectAsync(ObjectId sessionId);

    Task<PaginatedRecords<CashSessionListObject>> GetCashSessionsListAsync(int page = 1, int itemsPerPage = 10,
        DateTime? startDate = null, DateTime? endDate = null);
}



