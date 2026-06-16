using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Dashboard;

namespace Project.Gawad.Core.Providers;

public interface IDashboardProvider
{
    Task<DashboardStatsObject> GetDashboardStats();

    Task<PaginatedRecords<BirthdayResidentListObject>> GetTodayBirthdays(DateTime date);
}