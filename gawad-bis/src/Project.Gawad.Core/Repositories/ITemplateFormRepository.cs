using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums;

namespace Project.Gawad.Core.Repositories;

public interface ITemplateFormRepository : IBaseRepository<TemplateForm>
{
    public Task<TemplateForm?> GetActiveFormTemplate(BarangayDocumentType barangayDocumentType);
}