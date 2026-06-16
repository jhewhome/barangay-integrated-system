namespace Project.Gawad.Domain.Objects.Medicine;

public class MedicineBalanceSummaryObject
{
    public DateTime StartDate { get; set; }
    public DateTime EndDate { get; set; }
    
    /// <summary>
    /// Total count of pieces received (stock-in)
    /// </summary>
    public decimal TotalStockReceivedCount { get; set; }
    
    /// <summary>
    /// Total cost/value of all stock received (₱)
    /// </summary>
    public decimal TotalPurchases { get; set; }
    
    /// <summary>
    /// Total cost/value of all dispensed medicines (₱) - kept for backward compatibility
    /// </summary>
    public decimal TotalUsageValue { get; set; }
    
    /// <summary>
    /// Total count of pieces dispensed
    /// </summary>
    public decimal TotalDispensedCount { get; set; }
    
    /// <summary>
    /// Total count of pieces remaining in stock
    /// </summary>
    public decimal TotalRemainingStock { get; set; }
    
    public decimal Net => TotalPurchases - TotalUsageValue;
}





