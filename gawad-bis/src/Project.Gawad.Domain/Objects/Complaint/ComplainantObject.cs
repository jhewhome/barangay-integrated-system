using System;
using System.ComponentModel.DataAnnotations;
using System.Linq;
using MongoDB.Bson;
using Project.Gawad.Domain.Enums;

namespace Project.Gawad.Domain.Objects.Complaint;

public class ComplainantObject
{
    public ObjectId? Id { get; set; }

    public ObjectId PersonId { get; set; }

    public string ContactNumber { get; set; } = string.Empty;

    [Required(ErrorMessage = "First Name is required")]
    [Display(Name = "Given Name")] public string FirstName { get; set; } = string.Empty;

    [Required(ErrorMessage = "Last Name is required")]
    [Display(Name = "Family Name")] public string LastName { get; set; } = string.Empty;

    [Display(Name = "Midddle Name")] public string? MiddleName { get; set; }

    public string FullName
    => string.Join(" ",
        new[] {
          LastName,
          FirstName,
          string.IsNullOrWhiteSpace(MiddleName) ? null : MiddleName,
          string.IsNullOrWhiteSpace(Suffix)     ? null : Suffix
        }
        .Where(s => !string.IsNullOrWhiteSpace(s)));


    [Display(Name = "Suffix")] public string Suffix { get; set; } = string.Empty;

    [Display(Name = "Date of Birth")] public DateTime? DateOfBirth { get; set; }

    [Display(Name = "Place of Birth")] public string PlaceOfBirth { get; set; } = string.Empty;

    [Display(Name = "Gender")] public Gender Gender { get; set; }

    [Display(Name = "Civil Status")] public CivilStatus CivilStatus { get; set; }

    [Display(Name = "Name of Spouse (if applicable)")]
    public string SpouseName { get; set; } = string.Empty;

    [Display(Name = "Father Name")] public string FatherName { get; set; } = string.Empty;

    [Display(Name = "Mother Maiden Name")] public string MotherMaidenName { get; set; } = string.Empty;

    [Display(Name = "Voter ID No.")] public string VoterId { get; set; } = string.Empty;

    [Display(Name = "Precint No.")] public string PrecintNo { get; set; } = string.Empty;

    public ObjectId CreatedBy { get; set; }

    public ObjectId ModifiedBy { get; set; }

    public ObjectViewMode ViewMode { get; set; }

    #region PermanentAddress

    public ObjectId? PermAddAddressId { get; set; }

    [Display(Name = "Unit/Lot/Street Name")]
    public string CompAddressLine1 { get; set; } = string.Empty;

    [Display(Name = "Subdivision")] public string CompAddressLine2 { get; set; } = string.Empty;

    [Display(Name = "Zone")] public string CompAddZone { get; set; }

    [Display(Name = "Barangay")] public string CompAddBarangay { get; set; }

    [Display(Name = "City")] public string CompAddCity { get; set; }

    [Display(Name = "Province")] public string CompAddProvince { get; set; }

    [Display(Name = "Zip Code")] public string CompAddZipCode { get; set; }

    [Display(Name = "Country")] public string CompmAddCountry { get; set; }

    #endregion
}