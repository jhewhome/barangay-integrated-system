using System.ComponentModel.DataAnnotations;
using Project.Gawad.Domain.Enums.Transactions;

namespace Project.Gawad.Domain.Objects.Transactions;

public class TransactionDetailsObject
{
    [Display(Name = "Transaction ID")] public string Id { get; set; }

    [Display(Name = "Control Number")] public string ControlNumber { get; set; }

    [Display(Name = "Requester Full Name")]
    public string FullName { get; set; }

    [Display(Name = "Type of Transaction")]
    public TransactionType TransactionType { get; set; }

    [Display(Name = "Paid Amount")]
    [DisplayFormat(DataFormatString = "{0:N2}")]
    public double PaidAmount { get; set; }

    [Display(Name = "Transaction DateTime")]
    [DisplayFormat(DataFormatString = "{0:MMMMM d, yyyy hh:mm tt}")]
    public DateTime TransactionDateTime { get; set; }

    public string ResidentId { get; set; }

    public string PersonId { get; set; }

    [Display(Name = "OR No.")] public string? ReceiptNumber { get; set; }

    [Display(Name = "Notes")] public string? Notes { get; set; }

    [Display(Name = "Officer of the Day")] public string? OfficerOfTheDay { get; set; }
}