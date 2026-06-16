using System;
using System.Threading.Tasks;
using System.Collections.Generic;
using Microsoft.EntityFrameworkCore;
using MongoDB.Bson;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb; // ensure GawadMongoDbContext is visible
using Project.Gawad.Domain.Enums.Complaints;

namespace Project.Gawad.Infrastructure.Repositories
{
    public class ComplaintRepository : BaseRepository<Complaint>, IComplaintRepository
    {
        private readonly DbSet<Complaint> _set;

        // Call base constructor and initialize the DbSet
        public ComplaintRepository(GawadMongoDbContext dbContext) : base(dbContext)
        {
            if (dbContext == null) throw new ArgumentNullException(nameof(dbContext));
            _set = dbContext.Set<Complaint>();
        }

        // Return the tracked entity so its Id will be populated when SaveChanges is called
        public override async Task<Complaint> AddAsync(Complaint entity)
        {
            if (entity == null) throw new ArgumentNullException(nameof(entity));
            var entry = await _set.AddAsync(entity).ConfigureAwait(false);
            return entry.Entity;
        }

        public override async Task<Complaint> UpdateAsync(Complaint entity)
        {
            if (entity == null) throw new ArgumentNullException(nameof(entity));
            
            // Use direct _set.Update like other repositories for MongoDB compatibility
            var entry = _set.Update(entity);
            return await Task.FromResult(entry.Entity);
        }

        // IComplaintRepository implementation � no-op but functional queries

        // Returns all complaints
        public async Task<IReadOnlyList<Complaint>> GetAllAsync()
        {
            return await _set
                .AsNoTracking()
                .ToListAsync()
                .ConfigureAwait(false);
        }

        // Finds a complaint by CreatedById as a practical placeholder for the requested lookup.
        // Adjust predicate to the correct field if you have a specific foreign key to query.
        public async Task<Complaint?> GetClearanceByBarangayTransactionIdAsync(ObjectId personId)
        {
            // Example: lookup by CreatedById � replace with correct property if needed
            return await _set.AsNoTracking()
                             .FirstOrDefaultAsync(c => c.CreatedById == personId)
                             .ConfigureAwait(false);
        }

        // Update only the status field to avoid tracking issues with navigation properties
        public async Task UpdateStatusAsync(ObjectId id, ComplaintStatus newStatus)
        {
            var entity = await _set.FindAsync(id);
            if (entity != null)
            {
                entity.Status = newStatus;
                entity.LastModifiedDate = DateTime.UtcNow;
                await SaveChangesAsync();
            }
        }

        // other members can be added as needed...
    }
}