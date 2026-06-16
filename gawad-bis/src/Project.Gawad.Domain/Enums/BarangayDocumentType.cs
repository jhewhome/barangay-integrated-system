using System.ComponentModel.DataAnnotations;

namespace Project.Gawad.Domain.Enums;

public enum BarangayDocumentType
{
    [Display(Name = "Barangay Clearance")] BarangayClearance,
    [Display(Name = "Business Permit")] BusinessPermit,
    [Display(Name = "Barangay Id")] BarangayId,

    [Display(Name = "Certificate of Indigency")]
    CertificateOfIndigency
}