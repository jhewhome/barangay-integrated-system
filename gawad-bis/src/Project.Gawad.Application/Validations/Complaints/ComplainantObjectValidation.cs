using FluentValidation;
using Project.Gawad.Domain.Objects.Complaint;

namespace Project.Gawad.Application.Validations.Complaints
{
    public class ComplainantObjectValidation : AbstractValidator<ComplainantObject>
    {
        public ComplainantObjectValidation()
        {
            RuleFor(x => x.FirstName)
                .NotEmpty().WithMessage("Given Name must not be empty")
                .NotNull().WithMessage("Given Name must not be null");

            RuleFor(x => x.LastName)
                .NotEmpty().WithMessage("Family Name must not be empty")
                .NotNull().WithMessage("Family Name must not be null");

            RuleFor(x => x.DateOfBirth)
                .NotNull().WithMessage("Date of Birth must be provided")
                .LessThanOrEqualTo(DateTime.Today).WithMessage("Date of Birth cannot be in the future");

            RuleFor(x => x.Gender)
                .NotNull().WithMessage("Gender must be specified");

            RuleFor(x => x.CivilStatus)
                .NotNull().WithMessage("Civil Status must be specified");

            // Address fields
            RuleFor(x => x.CompAddressLine1)
                .NotEmpty().WithMessage("Address Line 1 must not be empty")
                .NotNull().WithMessage("Address Line 1 must not be null");
            RuleFor(x => x.CompAddBarangay)
                .NotEmpty().WithMessage("Barangay must not be empty")
                .NotNull().WithMessage("Barangay must not be null");
            RuleFor(x => x.CompAddCity)
                .NotEmpty().WithMessage("City must not be empty")
                .NotNull().WithMessage("City must not be null");
            RuleFor(x => x.CompAddProvince)
                .NotEmpty().WithMessage("Province must not be empty")
                .NotNull().WithMessage("Province must not be null");
            RuleFor(x => x.CompmAddCountry)
                .NotEmpty().WithMessage("Country must not be empty")
                .NotNull().WithMessage("Country must not be null");
        }
    }
}
