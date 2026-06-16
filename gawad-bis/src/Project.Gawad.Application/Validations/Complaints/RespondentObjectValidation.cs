using FluentValidation;
using Project.Gawad.Domain.Objects.Complaint;

namespace Project.Gawad.Application.Validations.Complaints
{
    public class RespondentObjectValidation : AbstractValidator<RespondentObject>
    {
        public RespondentObjectValidation()
        {
            RuleFor(x => x.FirstName)
                .NotEmpty().WithMessage("Given Name must not be empty")
                .NotNull().WithMessage("Given Name must not be null");

            RuleFor(x => x.LastName)
                .NotEmpty().WithMessage("Family Name must not be empty")
                .NotNull().WithMessage("Family Name must not be null");

            RuleFor(x => x.Address)
                .NotEmpty().WithMessage("Address must not be empty")
                .NotNull().WithMessage("Address must not be null");

            RuleFor(x => x.Age)
                .GreaterThan(0).WithMessage("Age must be a positive number")
                .When(x => x.Age.HasValue);

            RuleFor(x => x.Gender)
                .NotNull().WithMessage("Gender must be specified");

            RuleFor(x => x.CivilStatus)
                .NotNull().WithMessage("Civil Status must be specified");

            RuleFor(x => x.Occupation)
                .NotEmpty().WithMessage("Occupation must not be empty")
                .NotNull().WithMessage("Occupation must not be null");
        }
    }
}
