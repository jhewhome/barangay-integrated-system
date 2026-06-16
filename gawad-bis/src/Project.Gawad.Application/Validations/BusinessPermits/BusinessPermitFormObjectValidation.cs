using FluentValidation;
using Project.Gawad.Domain.Objects.BusinessPermit;

namespace Project.Gawad.Application.Validations.BusinessPermits;

public class BusinessPermitFormObjectValidation : AbstractValidator<BusinessPermitFormObject>
{
    public BusinessPermitFormObjectValidation()
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

        // personal address
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

        // business address
        RuleFor(x => x.BussAddressLine1)
            .NotEmpty()
            .WithMessage("Unit/Lot/Street Name must not be empty")
            .NotNull()
            .WithMessage("Unit/Lot/Street Name must not be null");

        RuleFor(x => x.BussBarangay)
            .NotEmpty()
            .WithMessage("Barangay must not be empty")
            .NotNull()
            .WithMessage("Barangay must not be null");

        RuleFor(x => x.BussCity)
            .NotEmpty()
            .WithMessage("City must not be empty")
            .NotNull()
            .WithMessage("City must not be null");

        RuleFor(x => x.BussProvince)
            .NotEmpty()
            .WithMessage("Province must not be empty")
            .NotNull()
            .WithMessage("Province must not be null");

        RuleFor(x => x.BussCountry)
            .NotEmpty()
            .WithMessage("Country must not be empty")
            .NotNull()
            .WithMessage("Country must not be null");

        RuleFor(x => x.ReceiptNumber)
            .NotEmpty()
            .WithMessage("O.R No. must not be empty")
            .NotNull()
            .WithMessage("O.R No. must not be empty");

        RuleFor(x => x.BusinessName)
            .NotEmpty()
            .WithMessage("Name of Business must not be empty")
            .NotNull()
            .WithMessage("Name of Business must not be empty");
    }
}