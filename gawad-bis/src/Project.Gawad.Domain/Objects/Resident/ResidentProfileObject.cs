using System;
using System.ComponentModel.DataAnnotations;

namespace Project.Gawad.Domain.Objects.Resident;

public class ResidentProfileObject
{
    // Personal Info
    public string Id { get; set; }

    [Display(Name = "Resident Full Name")] public string FullName { get; set; }

    [Display(Name = "Date of Birth")]
    [DisplayFormat(DataFormatString = "{0:MMMMM d, yyyy}")]
    public DateTime? DateOfBirth { get; set; }

    [Display(Name = "Place of Birth")] public string PlaceOfBirth { get; set; }

    [Display(Name = "Gender")] public string Gender { get; set; }

    [Display(Name = "Civil Status")] public string CivilStatus { get; set; }

    [Display(Name = "Name of Spouse")] public string? SpouseName { get; set; }

    [Display(Name = "Father Name")] public string FatherName { get; set; }

    [Display(Name = "Mother Maiden Name")] public string MotherMaidenName { get; set; }

    public string ContactNumber { get; set; }

    // Addresses 
    [Display(Name = "Permanent Address")] public string PermanentAddress { get; set; }

    [Display(Name = "Current Address")] public string CurrentAddress { get; set; }


    [Display(Name = "Voter ID No.")] public string VoterId { get; set; }
    [Display(Name = "Precint No.")] public string PrecintNo { get; set; }

    [Display(Name = "Age")] public string Age { get; set; }

    [Display(Name = "Date Registered")]
    [DisplayFormat(DataFormatString = "{0:MMMMM d, yyyy}")]
    public DateTime EnrolledDateTime { get; set; }


    [Display(Name = "Is PWD?")] public string IsPWD { get; set; }

    [Display(Name = "Is Registered Voter?")]
    public string IsRegisteredVoter { get; set; }

    [Display(Name = "Nationality")] public string Nationality { get; set; }
}