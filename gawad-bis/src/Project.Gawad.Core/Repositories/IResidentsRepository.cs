using MongoDB.Bson;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Core.Repositories;

public interface IResidentsRepository : IBaseRepository<Resident>
{
    Task<Resident?> GetResidentByPersonIdAsync(ObjectId personId);

    Task<int> GetResidentCountAsync();
    
    Task<IEnumerable<Resident>> GetResidentsWithBirthdaysTodayAsync(DateTime date);
}