using FluentValidation;
using Project.Gawad.Domain.Objects.Complaint;

namespace Project.Gawad.Application.Validations.Complaints
{
    public class ComplaintFormObjectValidation : AbstractValidator<ComplaintFormObject>
    {
        public ComplaintFormObjectValidation()
        {
            RuleFor(x => x.Complainants)
                .NotEmpty().WithMessage("At least one complainant is required")
                .ForEach(x => x.SetValidator(new ComplainantObjectValidation()));

            RuleFor(x => x.Respondents)
                .NotEmpty().WithMessage("At least one respondent is required")
                .ForEach(x => x.SetValidator(new RespondentObjectValidation()));

            RuleFor(x => x.ComplaintType)
                .NotNull().WithMessage("Complaint Type must be specified");

            RuleFor(x => x.Subject)
                .NotEmpty().WithMessage("Subject must not be empty")
                .MaximumLength(200).WithMessage("Subject must be at most 200 characters");

            RuleFor(x => x.Details)
                .MaximumLength(1000).WithMessage("Details must be at most 1000 characters");

            RuleFor(x => x.IncidentDateTime)
                .NotNull().WithMessage("Incident Date & Time must be provided")
                .LessThanOrEqualTo(DateTime.Now).WithMessage("Incident cannot be in the future");

            RuleFor(x => x.ReportedDate)
                .NotNull().WithMessage("Reported Date must be provided")
                .LessThanOrEqualTo(DateTime.Now).WithMessage("Reported Date cannot be in the future");
        }
    }
}
