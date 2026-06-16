using System.Linq.Expressions;
using AutoMapper;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.VisitorLog;

namespace Project.Gawad.Application.Providers;

public class VisitorLogsProvider(IVisitorLogRepository visitorLogRepository, IMapper mapper) : IVisitorLogsProvider
{
    private readonly IMapper _mapper =
        mapper ?? throw new ArgumentNullException(nameof(mapper));

    private readonly IVisitorLogRepository _visitorLogRepository =
        visitorLogRepository ?? throw new ArgumentNullException(nameof(visitorLogRepository));

    /// <inheritdoc/>
    public async Task<PaginatedRecords<VisitorLogListObject>> GetVisitorListAsync(int page = 1, int itemsPerPage = 10,
        int sortColIndex = 0, string sortColDir = "asc",
        string? search = null)
    {
        var isAscending = sortColDir.Equals("asc", StringComparison.InvariantCultureIgnoreCase);

        Expression<Func<VisitorLog, object>> order = sortColIndex switch
        {
            0 => a => a.CreatedDate,
            2 => a => a.FirstName,
            _ => order = a => a.CreatedDate
        };

        Func<VisitorLog, bool>? filter = string.IsNullOrEmpty(search)
            ? null
            : new Func<VisitorLog, bool>(a =>
                (a.FirstName.ToLower().Contains(search?.ToLower())
                 || (string.IsNullOrEmpty(a.MiddleName) ? false : a.MiddleName.ToLower().Contains(search?.ToLower()))
                 || a.LastName.ToLower().Contains(search?.ToLower())
                 || a.Purpose.ToLower().Contains(search?.ToLower()))
                && !a.IsDeleted
            );

        Func<VisitorLog, VisitorLog>? select = r => r;

        var paginatedResult = await _visitorLogRepository.GetPaginatedRecordsAsync(page, itemsPerPage,
            isAscending, select, order, filter);

        return new PaginatedRecords<VisitorLogListObject>
        {
            PageNumber = page,
            PageSize = itemsPerPage,
            RecordsTotal = paginatedResult.RecordsTotal,
            RecordsFiltered = paginatedResult.RecordsFiltered,
            Data = paginatedResult.Data.Select(a => _mapper.Map<VisitorLogListObject>(a)).ToList()
        };
    }
}