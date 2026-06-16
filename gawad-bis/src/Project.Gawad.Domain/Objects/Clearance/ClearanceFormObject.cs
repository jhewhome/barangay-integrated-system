using System.ComponentModel.DataAnnotations;
using MongoDB.Bson;
using Project.Gawad.Domain.Enums;
using Project.Gawad.Domain.Enums.Clearances;
using Project.Gawad.Domain.Enums.Transactions;

namespace Project.Gawad.Domain.Objects.Clearance;

public class ClearanceFormObject
{
    public ObjectId? Id { get; set; }

    public ObjectId? PersonId { get; set; }

    [Display(Name = "Given Name")] public string FirstName { get; set; }

    [Display(Name = "Family Name")] public string LastName { get; set; }

    [Display(Name = "Midddle Name")] public string? MiddleName { get; set; }

    [Display(Name = "Suffix")] public string Suffix { get; set; }

    [Display(Name = "Date of Birth")] public DateTime? DateOfBirth { get; set; }

    [Display(Name = "Place of Birth")] public string PlaceOfBirth { get; set; }

    [Display(Name = "Gender")] public Gender Gender { get; set; }

    [Display(Name = "Civil Status")] public CivilStatus CivilStatus { get; set; }

    [Display(Name = "Name of Spouse (if applicable)")]
    public string SpouseName { get; set; }

    [Display(Name = "Father Name")] public string FatherName { get; set; }

    [Display(Name = "Mother Maiden Name")] public string MotherMaidenName { get; set; }

    [Display(Name = "Nationality")] public string Nationality { get; set; }

    public TransactionType TransactionType { get; set; }

    public string ControlNumber { get; set; }

    [Display(Name = "Notes")] public string Notes { get; set; }

    [Display(Name = "Purpose of Clearance")]
    public ClearancePurpose ClearancePurpose { get; set; }

    [Display(Name = "Specify other Purpose")]
    public string Purpose { get; set; }

    [Display(Name = "O.R. No.")] public string ReceiptNumber { get; set; }

    [Display(Name = "Total Fee")] public float Fee { get; set; }

    [Display(Name = "Officer of the Day")] public string OfficerOfTheDay { get; set; }

    #region Address

    public ObjectId? AddressId { get; set; }

    [Display(Name = "Unit/Lot/Street Name")]
    public string AddressLine1 { get; set; }

    [Display(Name = "Subdivision")] public string AddressLine2 { get; set; }

    [Display(Name = "Zone")] public string Zone { get; set; }

    [Display(Name = "Barangay")] public string Barangay { get; set; }

    [Display(Name = "City")] public string City { get; set; }

    [Display(Name = "Province")] public string Province { get; set; }

    [Display(Name = "Zip Code")] public string ZipCode { get; set; }

    [Display(Name = "Country")] public string Country { get; set; }

    #endregion
    
    public ObjectId? TransactionId { get; set; }

}