using Project.Gawad.Application.Validations.Login;
using Project.Gawad.Domain.ViewModels.Login;
using Xunit;

namespace Project.Gawad.Tests.Application.Validations.Login;

public class LoginViewModelValidatorTests
{
    private LoginViewModelValidator _loginViewModelValidator;

    [Theory]
    [InlineData("admin", "admin", true, null)]
    [InlineData("", "admin", false, null)]
    public void Validate_Should_ReturnMatchExpectedResult(string username, string password, bool expectedResult,
        string? errorMessage)
    {
        _loginViewModelValidator = new LoginViewModelValidator();

        var result = _loginViewModelValidator.Validate(new LoginViewModel
        {
            Username = username,
            Password = password
        });

        Assert.Equal(result.IsValid, expectedResult);
    }
}