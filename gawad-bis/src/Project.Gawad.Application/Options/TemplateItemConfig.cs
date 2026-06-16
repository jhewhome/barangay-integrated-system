using Project.Gawad.Domain.Enums;

namespace Project.Gawad.Application.Options;

public class TemplateItemConfig
{
    public BarangayDocumentType Type { get; set; }

    public string FileTemplatePath { get; set; }

    public string Name { get; set; }
}