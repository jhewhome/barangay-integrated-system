using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.VisitorLog;

namespace Project.Gawad.Core.Providers;

public interface IVisitorLogsProvider
{
    /// <summary>
    /// Get the paginated list of visitors data
    /// </summary>
    /// <param name="page">Current page number</param>
    /// <param name="itemsPerPage">Number of records per pag</param>
    /// <param name="sortColIndex">Index of sort column</param>
    /// <param name="sortColDir">Specific order if 'asc' (ascending) or 'desc' (descending)</param>
    /// <param name="search">Search keyword to match</param>
    /// <returns></returns>
    Task<PaginatedRecords<VisitorLogListObject>> GetVisitorListAsync(int page = 1, int itemsPerPage = 10,
        int sortColIndex = 0, string sortColDir = "asc", string? search = null);
}