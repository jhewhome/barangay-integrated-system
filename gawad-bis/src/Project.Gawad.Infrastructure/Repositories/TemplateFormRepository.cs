using MongoDB.Driver.Linq;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums;

namespace Project.Gawad.Infrastructure.Repositories;

public class TemplateFormRepository(GawadMongoDbContext dbcontext)
    : BaseRepository<TemplateForm>(dbcontext), ITemplateFormRepository
{
    public Task<TemplateForm?> GetActiveFormTemplate(BarangayDocumentType barangayDocumentType)
    {
        return _dbContext.TemplateForms.FirstOrDefaultAsync(a => a.Type == barangayDocumentType && a.IsActive);
    }
}