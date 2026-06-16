using Microsoft.EntityFrameworkCore;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Infrastructure.Repositories;

public class ResidentsRepository(GawadMongoDbContext dbContext)
    : BaseRepository<Resident>(dbContext), IResidentsRepository
{
    public Task<Resident?> GetResidentByPersonIdAsync(ObjectId personId)
    {
        return _dbContext.Residents.AsNoTracking().FirstOrDefaultAsync(x => x!.PersonId == personId && !x.IsDeleted);
    }

    public Task<int> GetResidentCountAsync()
    {
        return _dbContext.Residents.AsNoTracking().Where(a => !a.IsDeleted).CountAsync();
    }

    public async Task<IEnumerable<Resident>> GetResidentsWithBirthdaysTodayAsync(DateTime date)
    {
        var day = date.Day;
        var month = date.Month;
        
        return await _dbContext.Residents
            .Include(r => r.Person)
            .AsNoTracking()
            .Where(r => !r.IsDeleted && 
                       r.Person != null && 
                       r.Person.DateOfBirth.Day == day && 
                       r.Person.DateOfBirth.Month == month)
            .ToListAsync();
    }

    // public override Task<Resident?> GetByIdAsync(ObjectId? id) =>
    // _dbContext.Residents
    //     .Include(a=>a.Person)
    //     .AsNoTracking()
    //     .FirstOrDefaultAsync(x => x.Id == id && !x.IsDeleted);
}