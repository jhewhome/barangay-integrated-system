using MongoDB.Bson;
using Project.Gawad.Domain.Entities;
using System.Collections.Generic;
using System.Threading.Tasks;

namespace Project.Gawad.Core.Repositories
{
    public interface IAttendeeRepository
    {
        Task<IReadOnlyList<Attendee>> GetAllAsync();
        Task<Attendee?> GetByIdAsync(ObjectId id);
        Task<IReadOnlyList<Attendee>> GetByEventIdAsync(ObjectId eventId);
        Task<Attendee> AddAsync(Attendee entity);
        Task<Attendee> UpdateAsync(Attendee entity);
        Task DeleteAsync(ObjectId id);
    }
}
