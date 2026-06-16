using System.ComponentModel.DataAnnotations;

namespace Project.Gawad.Domain.Enums.Medicine;

public enum MedicineCategory
{
    [Display(Name = "Antibacterial")]
    Antibacterial = 1,
    
    [Display(Name = "Maintenance")]
    Maintenance = 2,
    
    [Display(Name = "Antacid")]
    Antacid = 3,
    
    [Display(Name = "Analgesics / Antipyretics")]
    AnalgesicsAntipyretics = 4,
    
    [Display(Name = "Antihistamine / Antiasthmatics / Anti-Cough")]
    AntihistamineAntiasthmaticsAntiCough = 5,
    
    [Display(Name = "Micronutrients")]
    Micronutrients = 6,
    
    [Display(Name = "Others")]
    Others = 99
}




