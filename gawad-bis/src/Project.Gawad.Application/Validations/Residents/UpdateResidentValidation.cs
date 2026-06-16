using FluentValidation;
using Project.Gawad.Domain.Objects.Resident;

namespace Project.Gawad.Application.Validations.Residents;

public class UpdateResidentValidation : AbstractValidator<UpdateResidentObject>
{
    public UpdateResidentValidation()
    {
        RuleFor(x => x.Id)
            .NotNull()
            .WithMessage("Invalid id");

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
            .WithMessage("Civil Status must not be null");


        #region PermanentAddressValidations

        RuleFor(x => x.PermAddAddressLine1)
            .NotEmpty()
            .WithMessage("Unit/Lot/Street Name must not be empty")
            .NotNull()
            .WithMessage("Unit/Lot/Street Name must not be null");

        RuleFor(x => x.PermAddBarangay)
            .NotEmpty()
            .WithMessage("Barangay must not be empty")
            .NotNull()
            .WithMessage("Barangay must not be null");

        RuleFor(x => x.PermAddCity)
            .NotEmpty()
            .WithMessage("City must not be empty")
            .NotNull()
            .WithMessage("City must not be null");

        RuleFor(x => x.PermAddProvince)
            .NotEmpty()
            .WithMessage("Province must not be empty")
            .NotNull()
            .WithMessage("Province must not be null");

        RuleFor(x => x.PermAddCountry)
            .NotEmpty()
            .WithMessage("Country must not be empty")
            .NotNull()
            .WithMessage("Country must not be null");

        #endregion

        #region CurrentAddressValidations

        RuleFor(x => x.CurrAddAddressLine1)
            .NotEmpty()
            .WithMessage("Unit/Lot/Street Name must not be empty")
            .NotNull()
            .WithMessage("Unit/Lot/Street Name must not be null");

        RuleFor(x => x.CurrAddBarangay)
            .NotEmpty()
            .WithMessage("Barangay must not be empty")
            .NotNull()
            .WithMessage("Barangay must not be null");

        RuleFor(x => x.CurrAddCity)
            .NotEmpty()
            .WithMessage("City must not be empty")
            .NotNull()
            .WithMessage("City must not be null");

        RuleFor(x => x.CurrAddProvince)
            .NotEmpty()
            .WithMessage("Province must not be empty")
            .NotNull()
            .WithMessage("Province must not be null");

        RuleFor(x => x.CurrAddCountry)
            .NotEmpty()
            .WithMessage("Country must not be empty")
            .NotNull()
            .WithMessage("Country must not be null");

        #endregion
    }
}