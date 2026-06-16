using Project.Gawad.IntegrationTests.TestFixtures;

namespace Project.Gawad.IntegrationTests.ControllersTest;

public class AccountsControllerTest(WebAppFactoryFixture<Program> factoryFixture)
    : IClassFixture<WebAppFactoryFixture<Program>>
{
    private readonly HttpClient _client = factoryFixture.CreateClient();

    [Fact]
    public async Task AccountsController_GetIndex_Success()
    {
        var response = await _client.GetAsync("/");

        Assert.True(response.IsSuccessStatusCode);
    }
}