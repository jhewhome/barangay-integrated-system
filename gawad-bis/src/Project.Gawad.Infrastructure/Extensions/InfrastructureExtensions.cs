using AspNetCore.Identity.Mongo;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.Logging;
using MongoDB.Bson.Serialization;
using MongoDB.Bson.Serialization.IdGenerators;
using MongoDB.Driver;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Identity;
using Project.Gawad.Infrastructure.Repositories;

namespace Project.Gawad.Infrastructure.Extensions;

public static class InfrastructureExtensions
{
    private static readonly ILoggerFactory _loggerFactory = LoggerFactory.Create(builder => { builder.AddConsole(); });

    public static MongoClient _GawadMongoClient { get; set; }
    public static MongoClient _IdentityMongoClient { get; set; }

    public static IServiceCollection ConfigureDatabase(this IServiceCollection services, IConfiguration configuration)
    {
        var mongoDbSetting = configuration.GetSection("MongoDB").Get<MongoDbSettings>();

        _GawadMongoClient = new MongoClient(mongoDbSetting!.ConnectionString);
        services.AddDbContext<GawadMongoDbContext>(options =>
        {
            options.UseMongoDB(_GawadMongoClient, mongoDbSetting.GawadDatabaseName);
            options.UseLoggerFactory(_loggerFactory);
            // #if DEBUG
            options.LogTo(Console.WriteLine);
            options.EnableSensitiveDataLogging();
            // #endif
        });

        _IdentityMongoClient = new MongoClient(mongoDbSetting!.ConnectionString);
        services.AddDbContext<GawadIdentityMongoDbContext>(options =>
        {
            options.UseMongoDB(_IdentityMongoClient, mongoDbSetting.IdentityDatabaseName);
            options.UseLoggerFactory(_loggerFactory);
            // #if DEBUG
            options.LogTo(Console.WriteLine);
            options.EnableSensitiveDataLogging();
            // #endif
        });
        return services;
    }

    public static IServiceCollection SetUpIdentityContext(this IServiceCollection services,
        IConfiguration configuration)
    {
        BsonSerializer.RegisterIdGenerator(typeof(string), new StringObjectIdGenerator());

        // Add MongoDB and Identity setup (already configured in your project)
        services.AddIdentityMongoDbProvider<ApplicationUser, ApplicationRole>(options =>
        {
            // options.Password.RequiredLength = 6;
            // options.Password.RequireDigit = true;
        }, mongoOptions =>
        {
            var mongoDbSetting = configuration.GetSection("MongoDB").Get<MongoDbSettings>();
            var connectionString = $"{mongoDbSetting!.ConnectionString}/{mongoDbSetting.IdentityDatabaseName}";
            mongoOptions.ConnectionString = connectionString;
        });


        return services;
    }

    public static IServiceCollection AddGawadRepositories(this IServiceCollection services)
    {
        services
            .AddScoped<IComplaintRepository, ComplaintRepository>()
            .AddScoped<IResidentsRepository, ResidentsRepository>()
            .AddScoped<IUsersRepository, UsersRepository>()
            .AddScoped<IPersonRepository, PersonRepository>()
            .AddScoped<IClearanceRepository, ClearanceRepository>()
            .AddScoped<IBarangayTransactionRepository, BarangayTransactionRepository>()
            .AddScoped<IVisitorLogRepository, VisitorLogRepository>()
            .AddScoped<IBusinessPermitRepository, BusinessPermitRepository>()
            .AddScoped<IAttendeeRepository, AttendeeRepository>()
            .AddScoped<IMedicineRepository, MedicineRepository>()
            .AddScoped<IMedicineStockRepository, MedicineStockRepository>()
            .AddScoped<IMedicineTransactionRepository, MedicineTransactionRepository>()
            .AddScoped<IMedicineAuditLogRepository, MedicineAuditLogRepository>()
            .AddScoped<ISaleRepository, SaleRepository>()
            .AddScoped<ISaleItemRepository, SaleItemRepository>()
            .AddScoped<IPaymentRepository, PaymentRepository>()
            .AddScoped<ICashSessionRepository, CashSessionRepository>()
            .AddScoped<ICashMovementRepository, CashMovementRepository>()
            ;

        return services;
    }
}