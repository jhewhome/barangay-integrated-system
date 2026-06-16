using Project.Gawad.Domain.Enums;

namespace Project.Gawad.Domain.Entities;

public class TemplateForm : Entity
{
    public int TemplateFormId { get; set; }

    public string TemplatePathUri { get; set; }

    public string FormTemplateName { get; set; }

    public BarangayDocumentType Type { get; set; }

    public bool IsActive { get; set; }
}