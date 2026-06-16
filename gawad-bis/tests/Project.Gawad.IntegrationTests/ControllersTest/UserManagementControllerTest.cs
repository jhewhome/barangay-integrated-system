using Project.Gawad.IntegrationTests.TestFixtures;

namespace Project.Gawad.IntegrationTests.ControllersTest;

public class UserManagementControllerTest(WebAppFactoryFixture<Program> factoryFixture)
    : IClassFixture<WebAppFactoryFixture<Program>>
{
    private readonly HttpClient _client = factoryFixture.CreateClient();

    [Fact]
    public async Task UserManagementController_GetIndex_Success()
    {
        var response = await _client.GetAsync("/");

        Assert.True(response.IsSuccessStatusCode);
    }
}