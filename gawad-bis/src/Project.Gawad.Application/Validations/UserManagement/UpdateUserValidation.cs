using FluentValidation;
using Project.Gawad.Domain.Objects.UserManagement;

namespace Project.Gawad.Application.Validations.UserManagement;

public class UpdateUserValidation : AbstractValidator<UpdateUserObject>
{
    public UpdateUserValidation()
    {
        RuleFor(x => x.Id)
            .NotEmpty()
            .WithMessage("Object Id cannot be null")
            .NotNull()
            .WithMessage("Object Id cannot be null");

        RuleFor(x => x.Username)
            .NotEmpty()
            .WithMessage("Given Name must not be empty")
            .NotNull()
            .WithMessage("Given Name must not be null");

        RuleFor(x => x.Firstname)
            .NotEmpty()
            .WithMessage("Given Name must not be empty")
            .NotNull()
            .WithMessage("Given Name must not be null");

        RuleFor(x => x.Lastname)
            .NotEmpty()
            .WithMessage("Given Name must not be empty")
            .NotNull()
            .WithMessage("Given Name must not be null");
    }
}