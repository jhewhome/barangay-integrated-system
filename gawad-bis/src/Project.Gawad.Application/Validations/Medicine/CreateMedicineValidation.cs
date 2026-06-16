using FluentValidation;
using Project.Gawad.Domain.Objects.Medicine;

namespace Project.Gawad.Application.Validations.Medicine;

public class CreateMedicineValidation : AbstractValidator<CreateMedicineObject>
{
    public CreateMedicineValidation()
    {
        RuleFor(x => x.Name)
            .NotEmpty().WithMessage("Medicine name is required.")
            .MaximumLength(200).WithMessage("Medicine name must not exceed 200 characters.");

        RuleFor(x => x.Category)
            .IsInEnum().WithMessage("Invalid medicine category.");

        RuleFor(x => x.UnitOfMeasure)
            .IsInEnum().WithMessage("Invalid unit of measure.");

        RuleFor(x => x.MinimumStockLevel)
            .GreaterThanOrEqualTo(0).WithMessage("Minimum stock level must be 0 or greater.");

        RuleFor(x => x.UnitPrice)
            .GreaterThanOrEqualTo(0).When(x => x.UnitPrice.HasValue)
            .WithMessage("Unit price must be 0 or greater.");

        RuleFor(x => x.Description)
            .MaximumLength(1000).When(x => !string.IsNullOrEmpty(x.Description))
            .WithMessage("Description must not exceed 1000 characters.");
    }
}




