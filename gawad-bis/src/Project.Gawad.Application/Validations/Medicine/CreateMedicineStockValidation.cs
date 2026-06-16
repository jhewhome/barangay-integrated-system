using FluentValidation;
using Project.Gawad.Domain.Objects.Medicine;

namespace Project.Gawad.Application.Validations.Medicine;

public class CreateMedicineStockValidation : AbstractValidator<CreateMedicineStockObject>
{
    public CreateMedicineStockValidation()
    {
        RuleFor(x => x.MedicineId)
            .NotEmpty().WithMessage("Medicine is required.");

        RuleFor(x => x.Quantity)
            .GreaterThan(0).WithMessage("Quantity must be greater than 0.");

        RuleFor(x => x.CostPerUnit)
            .GreaterThanOrEqualTo(0).When(x => x.CostPerUnit.HasValue)
            .WithMessage("Cost per unit must be 0 or greater.");

        RuleFor(x => x.ExpiryDate)
            .GreaterThan(DateTime.Now).When(x => x.ExpiryDate.HasValue)
            .WithMessage("Expiry date must be in the future.");

        RuleFor(x => x.ReceivedDate)
            .LessThanOrEqualTo(DateTime.Now).When(x => x.ReceivedDate.HasValue)
            .WithMessage("Received date cannot be in the future.");
    }
}




