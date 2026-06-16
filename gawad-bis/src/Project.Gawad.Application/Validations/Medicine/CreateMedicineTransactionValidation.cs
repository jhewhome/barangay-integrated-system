using FluentValidation;
using Project.Gawad.Domain.Objects.Medicine;

namespace Project.Gawad.Application.Validations.Medicine;

public class CreateMedicineTransactionValidation : AbstractValidator<CreateMedicineTransactionObject>
{
    public CreateMedicineTransactionValidation()
    {
        RuleFor(x => x.MedicineId)
            .NotEmpty().WithMessage("Medicine is required.");

        RuleFor(x => x.TransactionType)
            .IsInEnum().WithMessage("Invalid transaction type.");

        RuleFor(x => x.Quantity)
            .GreaterThan(0).WithMessage("Quantity must be greater than 0.");

        RuleFor(x => x.TransactionDate)
            .LessThanOrEqualTo(DateTime.Now).WithMessage("Transaction date cannot be in the future.");

        RuleFor(x => x.UnitPrice)
            .GreaterThanOrEqualTo(0).When(x => x.UnitPrice.HasValue)
            .WithMessage("Unit price must be 0 or greater.");
    }
}




