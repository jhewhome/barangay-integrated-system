using System.ComponentModel.DataAnnotations;

namespace Project.Gawad.Domain.Enums.Clearances;

public enum ClearancePurpose
{
    [Display(Name = "Local Employment")] LocalEmployment,
    [Display(Name = "School Requirement")] SchoolRequirement,
    [Display(Name = "Senior Citizen")] SeniorCitizen,
    [Display(Name = "Postal ID")] PostalId,

    [Display(Name = "Application For Passport/Visa Application (Local / Abroad)")]
    ApplicationForPassport,
    [Display(Name = "Health Card")] HealthCard,
    [Display(Name = "Banks")] Banks,
    [Display(Name = "Police Clearance")] PoliceClearance,

    [Display(Name = "First Time Job Seeker")]
    FirstTimeJobSeeker,
    [Display(Name = "Bail Bond Purposes")] BailBondPurposes,
    [Display(Name = "Other Purposes")] OthersPurposes,
    [Display(Name = "Medical Assistance")] MedicalAssistance,
    [Display(Name = "Employment Abroad")] EmploymentAbroad
}