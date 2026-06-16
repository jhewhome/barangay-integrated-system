using AutoMapper;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Dashboard;

namespace Project.Gawad.Application.Providers;

public class DashboardProvider(
    IResidentsRepository residentsRepository,
    IVisitorLogRepository visitorLogRepository,
    IComplaintRepository complaintRepository,
    IBarangayTransactionRepository barangayTransactionRepository,
    IMapper mapper) : IDashboardProvider
{
    private readonly IBarangayTransactionRepository _barangayTransactionRepository =
        barangayTransactionRepository ?? throw new ArgumentNullException(nameof(barangayTransactionRepository));

    private readonly IComplaintRepository _complaintRepository =
        complaintRepository ?? throw new ArgumentNullException(nameof(complaintRepository));

    private readonly IMapper _mapper = mapper;

    private readonly IResidentsRepository _residentsRepository =
        residentsRepository ?? throw new ArgumentNullException(nameof(residentsRepository));

    private readonly IVisitorLogRepository _visitorLogRepository =
        visitorLogRepository ?? throw new ArgumentNullException(nameof(visitorLogRepository));

    public async Task<DashboardStatsObject> GetDashboardStats()
    {
        var visitorsCount = await _visitorLogRepository.GetRecentVisitorsCountAsync(7);
        var totalVisitors = await _visitorLogRepository.GetRecentVisitorsCountAsync(null);
        
        // Debug logging
        Console.WriteLine($"Dashboard Stats - Visitors (last 7 days): {visitorsCount}, Total visitors: {totalVisitors}");
        
        return new DashboardStatsObject
        {
            ResidentsCount = await _residentsRepository.GetResidentCountAsync(),
            VisitorsCount = visitorsCount,
            ActiveComplaintCount = 0, // await _complaintRepository.GetActiveComplaintCountAsync(),
            TransactionsCount = await _barangayTransactionRepository.GetRecentTransactionCountAsync(7)
        };
    }

    public async Task<PaginatedRecords<BirthdayResidentListObject>> GetTodayBirthdays(DateTime date)
    {
        try
        {
            Console.WriteLine($"DashboardProvider: Getting residents with birthdays for {date:yyyy-MM-dd}");
            var residents = await _residentsRepository.GetResidentsWithBirthdaysTodayAsync(date);
            Console.WriteLine($"DashboardProvider: Found {residents?.Count() ?? 0} residents");
            
            var birthdayObjects = _mapper.Map<IEnumerable<BirthdayResidentListObject>>(residents);
            Console.WriteLine($"DashboardProvider: Mapped to {birthdayObjects?.Count() ?? 0} birthday objects");
            
            return new PaginatedRecords<BirthdayResidentListObject>
            {
                Data = birthdayObjects?.ToList() ?? new List<BirthdayResidentListObject>(),
                RecordsTotal = birthdayObjects?.Count() ?? 0,
                RecordsFiltered = birthdayObjects?.Count() ?? 0,
                PageNumber = 1,
                PageSize = birthdayObjects?.Count() ?? 0
            };
        }
        catch (Exception ex)
        {
            Console.WriteLine($"DashboardProvider: Error getting today's birthdays: {ex.Message}");
            Console.WriteLine($"DashboardProvider: Stack trace: {ex.StackTrace}");
            throw;
        }
    }
}