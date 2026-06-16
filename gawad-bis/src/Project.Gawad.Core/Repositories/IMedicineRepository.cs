using MongoDB.Bson;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Core.Repositories;

public interface IMedicineRepository : IBaseRepository<Medicine>
{
    Task<Medicine?> GetMedicineByNameAsync(string name);
    
    Task<IEnumerable<Medicine>> GetLowStockMedicinesAsync();
    
    Task<IEnumerable<Medicine>> GetActiveMedicinesAsync();
}




