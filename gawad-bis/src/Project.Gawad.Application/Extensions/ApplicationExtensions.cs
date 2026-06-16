using Microsoft.Extensions.DependencyInjection;
using Project.Gawad.Application.Providers;
using Project.Gawad.Application.Services;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Services;

namespace Project.Gawad.Application.Extensions;

public static class ApplicationExtensions
{
    /// <summary>
    /// Register the Services
    /// </summary>
    /// <param name="services"></param>
    /// <returns></returns>
    public static IServiceCollection AddGawadServices(this IServiceCollection services)
    {
        services.AddScoped<IUsersService, UsersService>()
            .AddScoped<IResidentsService, ResidentsService>()
            .AddScoped<IBhcSsoLinkService, BhcSsoLinkService>()
            .AddScoped<IClearanceService, ClearanceService>()
            .AddScoped<IVisitorLogService, VisitorLogService>()      // <-- added
            .AddScoped<IBusinessPermitService, BusinessPermitService>()
            .AddScoped<IBarangayTransactionService, BarangayTransactionService>()
            .AddScoped<IComplaintService, ComplaintService>()
            .AddScoped<IMedicineService, MedicineService>()
            .AddScoped<IMedicineAuditLogService, MedicineAuditLogService>()
            .AddScoped<ISalesService, SalesService>()
            .AddScoped<ICashService, CashService>()
            ;


        services.AddScoped<IDocumentService, DocumentService>();

        return services;
    }
    
    /// <summary>
    /// Register the Providers
    /// </summary>
    /// <param name="services"></param>
    /// <returns></returns>
    public static IServiceCollection AddGawadProviders(this IServiceCollection services)
    {
        services.AddScoped<IResidentsProvider, ResidentsProvider>()
            .AddScoped<IUsersProvider, UsersProvider>()
            .AddScoped<IPersonProvider, PersonProvider>()
            .AddScoped<IVisitorLogsProvider, VisitorLogsProvider>()
            .AddScoped<IClearanceProvider, ClearanceProvider>()
            .AddScoped<IDashboardProvider, DashboardProvider>()
            .AddScoped<IBarangayTransactionProvider, BarangayTransactionProvider>()
            .AddScoped<IBusinessPermitProvider, BusinessPermitProvider>()
            .AddScoped<IComplaintProvider, ComplaintProvider>()
            .AddScoped<IMedicineProvider, MedicineProvider>()
            .AddScoped<ISalesProvider, SalesProvider>()
            .AddScoped<ICashProvider, CashProvider>()
            ;

        return services;
    }
}