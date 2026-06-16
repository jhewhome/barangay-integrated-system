namespace Project.Gawad.Domain.Objects.Transactions;

public class TransactionListObject
{
    public string Id { get; set; }

    public string ControlNumber { get; set; }

    public string RequesterName { get; set; }

    public string TransactionType { get; set; }

    public string PaidAmount { get; set; }

    public string CreatedOn { get; set; }
}