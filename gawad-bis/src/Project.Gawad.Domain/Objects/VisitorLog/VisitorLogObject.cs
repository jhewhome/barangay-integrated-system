using System.ComponentModel.DataAnnotations;

namespace Project.Gawad.Domain.Objects.VisitorLog;

public class VisitorLogObject
{
    [Display(Name = "Given Name / Pangalan")]
    public string FirstName { get; set; } = string.Empty;

    [Display(Name = "Middlename / Gitnang Pangalan")]
    public string? MiddleName { get; set; }

    [Display(Name = "Family Name / Apelyido")]
    public string LastName { get; set; } = string.Empty;

    [Display(Name = "Purpose of Visit / Layunin ng Pagbisita")]
    public string Purpose { get; set; } = string.Empty;
}