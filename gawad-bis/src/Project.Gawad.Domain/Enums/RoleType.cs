using System.ComponentModel.DataAnnotations;

namespace Project.Gawad.Domain.Enums;

public enum RoleType
{
    [Display(Name = "Administrator")] Administrator,
    [Display(Name = "Barangay Secretary")] BarangaySecretary,
    [Display(Name = "Kagawad")] Kagawad,
    [Display(Name = "Health Worker / Staff")] Staff
}