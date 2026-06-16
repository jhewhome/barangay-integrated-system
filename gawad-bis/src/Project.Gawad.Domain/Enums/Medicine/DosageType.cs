using System.ComponentModel.DataAnnotations;

namespace Project.Gawad.Domain.Enums.Medicine;

public enum DosageType
{
    [Display(Name = "Not Applicable")]
    NotApplicable = 0,
    
    [Display(Name = "Tablet")]
    Tablet = 1,
    
    [Display(Name = "Capsule")]
    Capsule = 2,
    
    [Display(Name = "Solution")]
    Solution = 3,
    
    [Display(Name = "Cream")]
    Cream = 4
}







