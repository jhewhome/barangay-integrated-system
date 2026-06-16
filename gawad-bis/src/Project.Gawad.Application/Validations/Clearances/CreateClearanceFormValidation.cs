using FluentValidation;
using Project.Gawad.Domain.Enums.Clearances;
using Project.Gawad.Domain.Objects.Clearance;

namespace Project.Gawad.Application.Validations.Clearances;

public class CreateClearanceFormValidation : AbstractValidator<ClearanceFormObject>
{
    public CreateClearanceFormValidation()
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

        RuleFor(x => x.DateOfBirth)
            .NotEmpty()
            .WithMessage("Date of Birth must not be empty")
            .NotNull()
            .WithMessage("Date of Birth must not be null")
            .LessThanOrEqualTo(x => x.DateOfBirth)
            .WithMessage("Date of Birth should be less than or equal to today");

        RuleFor(x => x.CivilStatus)
            .NotNull()
            .WithMessage("Civil Status must not be null");

        RuleFor(x => x.Gender)
            .NotNull()
            .WithMessage("Gender must not be null");

        // Purpose field must not be empty when the selected purpose is other
        RuleFor(x => x.Purpose)
            .NotNull().WithMessage("Clearance Purpose must not be null")
            .When(x => x.ClearancePurpose == ClearancePurpose.OthersPurposes);

        RuleFor(x => x.Purpose)
            .NotNull().WithMessage("Clearance Purpose must not be empty")
            .When(x => x.ClearancePurpose == ClearancePurpose.OthersPurposes);

        RuleFor(x => x.AddressLine1)
            .NotEmpty()
            .WithMessage("Unit/Lot/Street Name must not be empty")
            .NotNull()
            .WithMessage("Unit/Lot/Street Name must not be null");

        RuleFor(x => x.Barangay)
            .NotEmpty()
            .WithMessage("Barangay must not be empty")
            .NotNull()
            .WithMessage("Barangay must not be null");

        RuleFor(x => x.City)
            .NotEmpty()
            .WithMessage("City must not be empty")
            .NotNull()
            .WithMessage("City must not be null");

        RuleFor(x => x.Province)
            .NotEmpty()
            .WithMessage("Province must not be empty")
            .NotNull()
            .WithMessage("Province must not be null");

        RuleFor(x => x.Country)
            .NotEmpty()
            .WithMessage("Country must not be empty")
            .NotNull()
            .WithMessage("Country must not be null");
    }
}