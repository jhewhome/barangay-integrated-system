using FluentValidation;
using Project.Gawad.Domain.Objects.VisitorLog;

namespace Project.Gawad.Application.Validations.VisitorLogs;

public class VisitorLogValidation : AbstractValidator<VisitorLogObject>
{
    public VisitorLogValidation()
    {
        RuleFor(x => x.FirstName)
            .NotEmpty()
            .WithMessage("Given Name must not be empty")
            .NotNull()
            .WithMessage("Given Name must not be null");

        RuleFor(x => x.LastName)
            .NotEmpty()
            .WithMessage("Family Name must not be empty")
            .NotNull()
            .WithMessage("Family Name must not be null");


        RuleFor(x => x.Purpose)
            .NotEmpty()
            .WithMessage("Purpose must not be empty")
            .NotNull()
            .WithMessage("Purpose must not be null");
    }
}