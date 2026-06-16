using Microsoft.EntityFrameworkCore;
using MongoDB.EntityFrameworkCore.Extensions;
using Project.Gawad.Domain.Identity;

namespace Project.Gawad.Data.MongoDb;

public class GawadIdentityMongoDbContext : DbContext
{
    public GawadIdentityMongoDbContext()
    {
    }

    public GawadIdentityMongoDbContext(DbContextOptions<GawadIdentityMongoDbContext> options)
        : base(options)
    {
    }

    public DbSet<ApplicationUser> ApplicationUsers { get; set; }

    public DbSet<ApplicationRole> ApplicationRoles { get; set; }

    protected override void OnModelCreating(ModelBuilder modelBuilder)
    {
        base.OnModelCreating(modelBuilder);

        modelBuilder.Entity<ApplicationUser>().ToCollection("Users");
        modelBuilder.Entity<ApplicationRole>().ToCollection("Roles");
    }
}