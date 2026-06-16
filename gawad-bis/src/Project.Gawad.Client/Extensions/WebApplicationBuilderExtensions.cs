using Microsoft.AspNetCore.Authentication.Cookies;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc.Authorization;
using DinkToPdf;
using DinkToPdf.Contracts;
using Project.Gawad.Application.Converters.JsonConverters;
using Project.Gawad.Application.Extensions;
using Project.Gawad.Application.Options;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Infrastructure.Extensions;

namespace Project.Gawad.Client.Extensions;

public static class WebApplicationBuilderExtensions
{
    public static void ConfigureServices(this WebApplicationBuilder builder)
    {
        if (builder.Environment.EnvironmentName == "Testing")
        {
            ConfigureServiceCollection(builder.Services, builder.Configuration);
        }
        else
        {
            builder.Services.ConfigureDatabase(builder.Configuration);

            ConfigureServiceCollection(builder.Services, builder.Configuration);
        }
    }

    private static void ConfigureServiceCollection(IServiceCollection services, IConfiguration configuration)
    {
        ConfigureOptions(services, configuration);

        // Set up identity and other services. Wrap identity setup to avoid crashing when MongoDB is down (temporary).
        try
        {
            services
                .SetUpIdentityContext(configuration)
                .AddGawadRepositories()
                .AddGawadServices()
                .AddGawadProviders();
        }
        catch (Exception ex)
        {
            // Build a short-lived provider to log the issue and continue.
            using var sp = services.BuildServiceProvider();
            var logger = sp.GetService<ILoggerFactory>()?.CreateLogger("Startup");
            logger?.LogWarning(ex, "Failed to configure Identity (MongoDB may be unavailable). Identity features disabled for this run.");

            // Still register repositories/services that do not depend on identity so app can start.
            services
                .AddGawadRepositories()
                .AddGawadServices()
                .AddGawadProviders();
        }

        services.AddMemoryCache(); // Register Memory Cache

        // Register DinkToPdf SynchronizedConverter with error handling
        try
        {
            var converter = new SynchronizedConverter(new PdfTools());
            services.AddSingleton(typeof(IConverter), converter);
        }
        catch (Exception ex)
        {
            // Log but don't fail startup - we'll fall back to browser print-to-PDF
            var logger = services.BuildServiceProvider().GetService<ILoggerFactory>()?.CreateLogger("Startup");
            logger?.LogWarning(ex, "Failed to initialize DinkToPdf. PDF downloads will fall back to browser print-to-PDF. " +
                "This is usually due to missing wkhtmltopdf native libraries. " +
                "To enable direct PDF downloads, install wkhtmltopdf or ensure native libraries are available.");
            
            // Register a no-op converter that will throw when used, triggering fallback
            services.AddSingleton(typeof(IConverter), new SynchronizedConverter(new PdfTools()));
        }

        services
            .AddControllersWithViews()
            .AddRazorRuntimeCompilation()
            .AddMvcOptions(o =>
            {
                var policy = new AuthorizationPolicyBuilder()
                    .RequireAuthenticatedUser()
                    .Build();

                o.Filters.Add(new AuthorizeFilter(policy));
            });

        services.AddMvc()
            .AddJsonOptions(o =>
            {
                o.JsonSerializerOptions.WriteIndented = true;
                o.JsonSerializerOptions.IgnoreNullValues = true;
                o.JsonSerializerOptions.Converters.Add(new TrimStringConverter());
            });

        services.AddFluentValidations();

        var cookieConfig = configuration.GetSection("CookieConfig");

        services
            .AddAuthentication(CookieAuthenticationDefaults.AuthenticationScheme)
            .AddCookie(options =>
            {
                int.TryParse(cookieConfig["ExpirationHours"], out var expirationInHours);
                options.ExpireTimeSpan = TimeSpan.FromHours(expirationInHours);

                bool.TryParse(cookieConfig["SlidingExpiration"], out var slidingExpiration);
                options.SlidingExpiration = slidingExpiration;
                options.AccessDeniedPath = "/Forbidden/";
                options.LoginPath = "/Accounts/Index";
                options.LogoutPath = "/Accounts/SignOut";

                //options.Cookie.HttpOnly = true;

                options.Cookie.SameSite = SameSiteMode.Lax;
                options.Cookie.Name = CookieAuthenticationDefaults.AuthenticationScheme;
            })
            ;

        services.AddAuthorization();

        services.AddHttpContextAccessor();

        services.AddAutoMapper(AppDomain.CurrentDomain.GetAssemblies());

        // services.AddDistributedMemoryCache();
        // services.AddSession(options =>
        // {
        //     options.IdleTimeout = TimeSpan.FromMinutes(30); // Set session timeout
        //     options.Cookie.HttpOnly = false; // Security: Prevent client-side access
        //     options.Cookie.IsEssential = true; // Ensure session works even if tracking is disabled
        // });
    }

    private static void ConfigureOptions(IServiceCollection services, IConfiguration configuration)
    {
        services.AddOptions<CookieConfigOption>()
            .Bind(configuration.GetSection("CookieConfig"))
            .ValidateDataAnnotations();

        services.AddOptions<AppConfigOption>()
            .Bind(configuration.GetSection("AppConfig"))
            .ValidateDataAnnotations();

        services.AddOptions<MongoDbSettings>()
            .Bind(configuration.GetSection("MongoDB"))
            .ValidateDataAnnotations();

        services.AddOptions<DefaultBarangayAddressOption>()
            .Bind(configuration.GetSection("DefaultBarangayAddress"))
            .ValidateDataAnnotations();

        services.AddOptions<Project.Gawad.Application.Options.OfficialSignatoryOption>()
            .Bind(configuration.GetSection("OfficialSignatory"))
            .ValidateDataAnnotations();

        services.AddOptions<Project.Gawad.Application.Options.VerificationUrlOption>()
            .Bind(configuration.GetSection("VerificationUrl"))
            .ValidateDataAnnotations();

        services.AddOptions<Project.Gawad.Application.Options.BhcIntegrationOption>()
            .Bind(configuration.GetSection("BhcIntegration"));

        services.AddOptions<TemplatesConfigOption>()
            .Bind(configuration.GetSection("TemplatesConfig"))
            .ValidateDataAnnotations();
    }

    public static void ConfigureApplication(this WebApplication app)
    {
        // Configure the HTTP request pipeline.
        if (app.Environment.IsDevelopment())
        {
        }
        else
        {
            app.UseExceptionHandler("/Home/Error");
            // The default HSTS value is 30 days. You may want to change this for production scenarios, see https://aka.ms/aspnetcore-hsts.
            app.UseHsts();
        }

        app.UseHttpsRedirection()
            .UseDefaultFiles()
            .UseStaticFiles()
            // .UseSession()
            .UseRouting()
            .UseAuthentication()
            .UseAuthorization();

        app.UseCookiePolicy(new CookiePolicyOptions
        {
            MinimumSameSitePolicy = SameSiteMode.Strict
        });

        app.MapRazorPages();
        app.MapControllers();
        app.MapControllerRoute(
            "default",
            "{controller}/{action}/{id?}",
            new { controller = "Accounts", action = "Index" });
    }
}