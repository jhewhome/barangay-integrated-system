using Microsoft.EntityFrameworkCore;
using MongoDB.EntityFrameworkCore.Extensions;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Data.MongoDb;

public class GawadMongoDbContext : DbContext
{
    public GawadMongoDbContext()
    {
    }

    public GawadMongoDbContext(DbContextOptions<GawadMongoDbContext> options)
        : base(options)
    {
    }

    public DbSet<Person?> Persons { get; set; }

    public DbSet<Resident?> Residents { get; set; }

    public DbSet<Complaint> Complaints { get; set; }

    public DbSet<BarangayTrasaction> BarangayTrasactions { get; set; }

    public DbSet<Clearance> Clearances { get; set; }

    public DbSet<VisitorLog> VisitorLogs { get; set; }

    public DbSet<BusinessPermit> BusinessPermits { get; set; }

    public DbSet<Attendee> Attendees { get; set; }

    public DbSet<TemplateForm> TemplateForms { get; set; }

    public DbSet<Medicine> Medicines { get; set; }

    public DbSet<MedicineStock> MedicineStocks { get; set; }

    public DbSet<MedicineTransaction> MedicineTransactions { get; set; }

    public DbSet<MedicineAuditLog> MedicineAuditLogs { get; set; }

    public DbSet<Sale> Sales { get; set; }

    public DbSet<SaleItem> SaleItems { get; set; }

    public DbSet<Payment> Payments { get; set; }

    public DbSet<CashSession> CashSessions { get; set; }

    public DbSet<CashMovement> CashMovements { get; set; }

    protected override void OnModelCreating(ModelBuilder modelBuilder)
    {
        base.OnModelCreating(modelBuilder);

        modelBuilder.Entity<Person>().ToCollection(nameof(Person));
        modelBuilder.Entity<Resident>().ToCollection(nameof(Resident));
        modelBuilder.Entity<Complaint>().ToCollection(nameof(Complaint));
        modelBuilder.Entity<Clearance>().ToCollection(nameof(Clearance));
        modelBuilder.Entity<BarangayTrasaction>().ToCollection(nameof(BarangayTrasaction));
        modelBuilder.Entity<VisitorLog>().ToCollection(nameof(VisitorLog));
        modelBuilder.Entity<BusinessPermit>().ToCollection(nameof(BusinessPermit));
        modelBuilder.Entity<Attendee>().ToCollection(nameof(Attendee));
        modelBuilder.Entity<Medicine>().ToCollection(nameof(Medicine));
        modelBuilder.Entity<MedicineStock>().ToCollection(nameof(MedicineStock));
        modelBuilder.Entity<MedicineTransaction>().ToCollection(nameof(MedicineTransaction));
        modelBuilder.Entity<MedicineAuditLog>().ToCollection(nameof(MedicineAuditLog));
        modelBuilder.Entity<Sale>().ToCollection(nameof(Sale));
        modelBuilder.Entity<SaleItem>().ToCollection(nameof(SaleItem));
        modelBuilder.Entity<Payment>().ToCollection(nameof(Payment));
        modelBuilder.Entity<CashSession>().ToCollection(nameof(CashSession));
        modelBuilder.Entity<CashMovement>().ToCollection(nameof(CashMovement));
    }
}