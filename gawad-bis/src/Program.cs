using System;
using Microsoft.AspNetCore.Builder;
using Microsoft.AspNetCore.Authentication.Cookies;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.Hosting;
using Microsoft.Extensions.Logging;

var builder = WebApplication.CreateBuilder(args);

// Logging to console so we can see startup info when running with dotnet run
builder.Logging.ClearProviders();
builder.Logging.AddConsole();

// Add services
builder.Services.AddControllersWithViews();
builder.Services.AddRazorPages();

// Provide a minimal authentication registration so UseAuthentication() won't blow up
builder.Services.AddAuthentication(CookieAuthenticationDefaults.AuthenticationScheme)
    .AddCookie(options =>
    {
        options.LoginPath = "/Accounts/Index";
        options.Cookie.Name = "ProjectGawadAuth";
    });

var app = builder.Build();

app.Lifetime.ApplicationStarted.Register(() =>
{
    Console.WriteLine("Application started.");
    Console.WriteLine("Listening on: " + string.Join(", ", app.Urls));
});

if (app.Environment.IsDevelopment())
{
    app.UseDeveloperExceptionPage();
}
else
{
    app.UseExceptionHandler("/Home/Error");
    app.UseHsts();
}

app.UseStaticFiles();
app.UseRouting();

app.UseAuthentication();
app.UseAuthorization();

// Quick health/test endpoint for debugging
app.MapGet("/", () => Results.Text("App is running. Try /Accounts/Index or /Complaints/Index", "text/plain"));

// Default route
app.MapControllerRoute(
    name: "default",
    pattern: "{controller=Accounts}/{action=Index}/{id?}");

try
{
    app.Run();
}
catch (Exception ex)
{
    var logger = app.Services.GetService<ILoggerFactory>()?.CreateLogger("Program");
    logger?.LogCritical(ex, "Host terminated unexpectedly");
    Console.WriteLine("Host terminated unexpectedly: " + ex);
    throw;
}