using Microsoft.EntityFrameworkCore;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Infrastructure.Repositories
{
    public class AttendeeRepository : BaseRepository<Attendee>, IAttendeeRepository
    {
        private readonly DbSet<Attendee> _set;

        public AttendeeRepository(GawadMongoDbContext dbContext) : base(dbContext)
        {
            if (dbContext == null) throw new ArgumentNullException(nameof(dbContext));
            _set = dbContext.Set<Attendee>();
        }

        public override async Task<Attendee> AddAsync(Attendee entity)
        {
            if (entity == null) throw new ArgumentNullException(nameof(entity));
            var entry = await _set.AddAsync(entity).ConfigureAwait(false);
            return entry.Entity;
        }

        public override async Task<Attendee> UpdateAsync(Attendee entity)
        {
            if (entity == null) throw new ArgumentNullException(nameof(entity));
            var entry = _set.Update(entity);
            return await Task.FromResult(entry.Entity);
        }

        public async Task<IReadOnlyList<Attendee>> GetAllAsync()
        {
            return await _set
                .AsNoTracking()
                .ToListAsync()
                .ConfigureAwait(false);
        }

        public async Task<Attendee?> GetByIdAsync(ObjectId id)
        {
            return await _set
                .AsNoTracking()
                .FirstOrDefaultAsync(x => x.Id == id && !x.IsDeleted)
                .ConfigureAwait(false);
        }

        public async Task<IReadOnlyList<Attendee>> GetByEventIdAsync(ObjectId eventId)
        {
            return await _set
                .AsNoTracking()
                .Where(x => x.EventId == eventId && !x.IsDeleted)
                .ToListAsync()
                .ConfigureAwait(false);
        }

        public async Task DeleteAsync(ObjectId id)
        {
            var entity = await _set.FirstOrDefaultAsync(x => x.Id == id && !x.IsDeleted);
            if (entity != null)
            {
                entity.IsDeleted = true;
                entity.LastModifiedDate = DateTime.UtcNow;
                _set.Update(entity);
            }
        }
    }
}
