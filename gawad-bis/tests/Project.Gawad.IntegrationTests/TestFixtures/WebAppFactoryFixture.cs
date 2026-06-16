using Microsoft.AspNetCore.Hosting;
using Microsoft.AspNetCore.Mvc.Testing;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.DependencyInjection;
using Project.Gawad.Data.MongoDb;

namespace Project.Gawad.IntegrationTests.TestFixtures;

public class WebAppFactoryFixture<TProgram>
    : WebApplicationFactory<TProgram> where TProgram : class
{
    protected override void ConfigureWebHost(IWebHostBuilder builder)
    {
        builder
            .UseEnvironment("Testing")
            .ConfigureServices(services =>
            {
                var context =
                    services.FirstOrDefault(descriptor => descriptor.ServiceType == typeof(GawadMongoDbContext));
                if (context != null)
                {
                    services.Remove(context);
                    var options = services.Where(r => r.ServiceType == typeof(DbContextOptions)
                                                      || (r.ServiceType.IsGenericType &&
                                                          r.ServiceType.GetGenericTypeDefinition() ==
                                                          typeof(DbContextOptions<>))).ToArray();
                    foreach (var option in options) services.Remove(option);
                }

                // Add a new registration for ApplicationDbContext with an in-memory database
                services.AddDbContext<GawadMongoDbContext>(options =>
                {
                    // Provide a unique name for your in-memory database
                    // options.UseInMemoryDatabase("InMemoryGawadDb");
                });
            });
    }
}