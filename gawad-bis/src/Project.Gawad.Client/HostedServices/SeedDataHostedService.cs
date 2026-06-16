using Microsoft.AspNetCore.Identity;
using Microsoft.Extensions.Options;
using MongoDB.Bson;
using Project.Gawad.Application.Options;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Enums;
using Project.Gawad.Domain.Identity;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.Resident;

namespace Project.Gawad.Client.HostedServices;

public class SeedDataHostedService(IServiceProvider serviceProvider) : IHostedService
{
    public async Task StartAsync(CancellationToken cancellationToken)
    {
        // Resolve the necessary services
        using var scope = serviceProvider.CreateScope();
        var userManager = scope.ServiceProvider.GetRequiredService<UserManager<ApplicationUser>>();
        var roleManager = scope.ServiceProvider.GetRequiredService<RoleManager<ApplicationRole>>();
        var residentsService = scope.ServiceProvider.GetRequiredService<IResidentsService>();
        var defaultBarangayAddress = scope.ServiceProvider.GetRequiredService<IOptions<DefaultBarangayAddressOption>>().Value;

        var adminUserId = ObjectId.GenerateNewId();
        var adminRoleId = ObjectId.GenerateNewId();

        var secretaryUserId = ObjectId.GenerateNewId();
        var secretaryRoleId = ObjectId.GenerateNewId();

        var kagawadUserId = ObjectId.GenerateNewId();
        var kagawadRoleId = ObjectId.GenerateNewId();

        var staffUserId = ObjectId.GenerateNewId();
        var staffRoleId = ObjectId.GenerateNewId();

        var roles = new List<ApplicationRole>
        {
            new()
            {
                Name = "Administrator",
                NormalizedName = "ADMINISTRATOR",
                Id = adminRoleId,
                ConcurrencyStamp = ObjectId.GenerateNewId().ToString(),
                Type = RoleType.Administrator
            },
            new()
            {
                Name = "Barangay Secretary",
                NormalizedName = "BARANGAY SECRETARY",
                Id = secretaryRoleId,
                ConcurrencyStamp = ObjectId.GenerateNewId().ToString(),
                Type = RoleType.BarangaySecretary
            },
            new()
            {
                Name = "Kagawad",
                NormalizedName = "KAGAWAD",
                Id = kagawadRoleId,
                ConcurrencyStamp = ObjectId.GenerateNewId().ToString(),
                Type = RoleType.Kagawad
            },
            new()
            {
                Name = "Health Worker / Staff",
                NormalizedName = "HEALTH WORKER / STAFF",
                Id = staffRoleId,
                ConcurrencyStamp = ObjectId.GenerateNewId().ToString(),
                Type = RoleType.Staff
            }
        };

        var users = new List<ApplicationUser>
        {
            new()
            {
                Id = adminUserId,
                EmailConfirmed = true,
                FirstName = "Admin",
                LastName = "User",
                UserName = "admin",
                NormalizedUserName = "ADMIN",
                TwoFactorEnabled = false,
                LockoutEnabled = false,
                SecurityStamp = ObjectId.GenerateNewId().ToString(),
                RoleType = RoleType.Administrator,
                CreatedDateTime = DateTime.UtcNow
            },
            new()
            {
                Id = secretaryUserId,
                EmailConfirmed = true,
                FirstName = "Secretary",
                LastName = "User",
                UserName = "secretary",
                NormalizedUserName = "SECRETARY",
                TwoFactorEnabled = false,
                LockoutEnabled = false,
                SecurityStamp = ObjectId.GenerateNewId().ToString(),
                RoleType = RoleType.BarangaySecretary,
                CreatedDateTime = DateTime.UtcNow
            },
            new()
            {
                Id = kagawadUserId,
                EmailConfirmed = true,
                FirstName = "Kagawad",
                LastName = "User",
                UserName = "kagawad",
                NormalizedUserName = "KAGAWAD",
                TwoFactorEnabled = false,
                LockoutEnabled = false,
                SecurityStamp = ObjectId.GenerateNewId().ToString(),
                RoleType = RoleType.Kagawad,
                CreatedDateTime = DateTime.UtcNow
            },
            new()
            {
                Id = staffUserId,
                EmailConfirmed = true,
                FirstName = "Staff",
                LastName = "User",
                UserName = "staff",
                NormalizedUserName = "STAFF",
                TwoFactorEnabled = false,
                LockoutEnabled = false,
                SecurityStamp = ObjectId.GenerateNewId().ToString(),
                RoleType = RoleType.Staff,
                CreatedDateTime = DateTime.UtcNow
            }
        };

        // Seed roles
        await SeedRolesAsync(roleManager, roles);

        // Seed users
        await SeedUsersAsync(userManager, users, roles);

        // Seed sample residents - REMOVED
        // Sample resident seeding functionality has been removed
    }

    public Task StopAsync(CancellationToken cancellationToken)
    {
        // Nothing to clean up
        return Task.CompletedTask;
    }

    private async Task SeedRolesAsync(RoleManager<ApplicationRole> roleManager, List<ApplicationRole> roles)
    {
        foreach (var role in roles)
            if (!await roleManager.RoleExistsAsync(role.Name!))
                await roleManager.CreateAsync(role);
    }

    private async Task SeedUsersAsync(UserManager<ApplicationUser> userManager, List<ApplicationUser> users,
        List<ApplicationRole> roles)
    {
        foreach (var user in users)
            if (user.UserName != null)
            {
                var existingUser = await userManager.FindByNameAsync(user.UserName);
                if (existingUser is null)
                {
                    await userManager.CreateAsync(user, "P@ssw0rd123!");

                    await userManager.AddToRoleAsync(user, roles.FirstOrDefault(a => a.Type == user.RoleType)!.Name!);
                }
            }
    }
}
