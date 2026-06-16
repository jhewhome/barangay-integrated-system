using FluentValidation;
using Project.Gawad.Domain.Objects.UserManagement;

namespace Project.Gawad.Application.Validations.UserManagement;

public class CreateUserValidation : AbstractValidator<CreateUserObject>
{
    public CreateUserValidation()
    {
        RuleFor(x => x.Username)
            .NotEmpty()
            .WithMessage("Username must not be empty")
            .NotNull()
            .WithMessage("Username must not be null");

        RuleFor(x => x.Firstname)
            .NotEmpty()
            .WithMessage("First Name must not be empty")
            .NotNull()
            .WithMessage("First Name must not be null");

        RuleFor(x => x.Lastname)
            .NotEmpty()
            .WithMessage("Last Name must not be empty")
            .NotNull()
            .WithMessage("Last Name must not be null");

        RuleFor(x => x.Password)
            .MinimumLength(6)
            .When(x => !string.IsNullOrWhiteSpace(x.Password))
            .WithMessage("Password must be at least 6 characters when provided");
    }
}