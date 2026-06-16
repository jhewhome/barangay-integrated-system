using FluentValidation;
using Project.Gawad.Domain.ViewModels.Login;

namespace Project.Gawad.Application.Validations.Login;

public class LoginViewModelValidator : AbstractValidator<LoginViewModel>
{
    public LoginViewModelValidator()
    {
        RuleFor(l => l.Username)
            .NotNull()
            .NotEmpty()
            .WithMessage("Username is required");

        RuleFor(l => l.Password)
            .NotNull()
            .NotEmpty()
            .WithMessage("Password is required");
    }
}