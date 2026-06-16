using System.ComponentModel.DataAnnotations;

namespace Project.Gawad.Domain.Enums.BusinessPermits;

public enum BusinessType
{
    [Display(Name = "Sari Sari Store")] SarisariStore,

    [Display(Name = "Sari Sari Store (Not Selling Liquor)")]
    SarisariStoreNotSellingLiquior,

    [Display(Name = "Single Proprietorship")]
    SingleProprietorship,
    [Display(Name = "Corporation")] Corporation,
    [Display(Name = "Cooperative")] Cooperative
}