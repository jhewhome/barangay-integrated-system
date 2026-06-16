using Microsoft.Extensions.Caching.Memory;
using Microsoft.Extensions.Options;
using Moq;
using Project.Gawad.Application.Options;
using Project.Gawad.Application.Services;
using Project.Gawad.Domain.Enums;
using Xunit;

namespace Project.Gawad.Tests.Application.Services;

public class DocumentServiceTests
{
    [Fact]
    public async Task GenerateCertificate_Should_ReturnClearancePdfFilestream_When_BarangayDocumentTypeIsClearance()
    {
        Mock<IOptions<TemplatesConfigOption>> templateConfigOptionsMock = new Mock<IOptions<TemplatesConfigOption>>();
        Mock<IMemoryCache> memoryCacheMock = new Mock<IMemoryCache>();

        templateConfigOptionsMock.Setup(t => t.Value).Returns(new TemplatesConfigOption
        {
            Templates = new[]
            {
                new TemplateItemConfig
                {
                    FileTemplatePath = "./Application/Services/test_templates/barangayclearance.docx",
                    Type = BarangayDocumentType.BarangayClearance
                },
                new TemplateItemConfig { FileTemplatePath = "", Type = BarangayDocumentType.BusinessPermit },
                new TemplateItemConfig { FileTemplatePath = "", Type = BarangayDocumentType.BarangayId }
            }
        });

        Dictionary<string, string> data = new Dictionary<string, string>
        {
            { "{fullName}", "Test" },
            { "{address}", "Test Address" },
            { "{purpose}", "For test purposes" },
            { "{issuedDate}", DateTime.Now.ToShortDateString() }
        };

        var templateFormGenerateService =
            new DocumentService(templateConfigOptionsMock.Object, memoryCacheMock.Object);

        var result =
            templateFormGenerateService.GenerateDocument("123455", BarangayDocumentType.BarangayClearance, data);

        Assert.NotNull(result);
    }
}