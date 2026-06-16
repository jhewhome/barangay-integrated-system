using FluentValidation;
using Project.Gawad.Domain.Objects.UserManagement;

namespace Project.Gawad.Application.Validations.UserManagement;

public class ChangePasswordObjectValidation : AbstractValidator<ChangePasswordObject>
{
    public ChangePasswordObjectValidation()
    {
        RuleFor(x => x.CurrentPassword)
            .NotEmpty()
            .WithMessage("Current Password must not be empty")
            .NotNull()
            .WithMessage("Current Password must not be null");

        RuleFor(x => x.NewPassword)
            .NotEmpty()
            .WithMessage("New Password must not be empty")
            .NotNull()
            .WithMessage("New Password must not be null");

        RuleFor(x => x.ConfirmNewPassword)
            .NotEmpty()
            .WithMessage("Confirm New Password must not be empty")
            .NotNull()
            .WithMessage("Confirm New Password must not be null");

        RuleFor(x => x.NewPassword)
            .Equal(x => x.ConfirmNewPassword)
            .WithMessage("New passwords do not match");
    }
}