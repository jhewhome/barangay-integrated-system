using Project.Gawad.Client.Extensions;
using Project.Gawad.Client.HostedServices;

var builder = WebApplication.CreateBuilder(args);

builder.ConfigureServices();
builder.Services.AddHostedService<SeedDataHostedService>();
var app = builder.Build();

app.ConfigureApplication();

app.Run();

public partial class Program
{
}