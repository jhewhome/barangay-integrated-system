using System.ComponentModel.DataAnnotations;
using MongoDB.Bson;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums;

namespace Project.Gawad.Domain.Objects.Resident;

public class UpdateResidentObject
{
    public ObjectId? Id { get; set; }

    public ObjectId PersonId { get; set; }

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

    [Display(Name = "Voter ID No.")] public string VoterId { get; set; }

    [Display(Name = "Precint No.")] public string PrecintNo { get; set; }

    [Display(Name = "Is Registered Voter?")]
    public bool IsRegisteredVoter { get; set; }

    [Display(Name = "Is PWD?")] public bool IsPWD { get; set; }

    public ObjectId ModifiedBy { get; set; }

    public Address GetPermanentAddress()
    {
        return new Address
        {
            Id = PermAddAddressId,
            AddressLine1 = PermAddAddressLine1,
            AddressLine2 = PermAddAddressLine2,
            Barangay = PermAddBarangay,
            City = PermAddCity,
            Province = PermAddProvince,
            ZipCode = PermAddZipCode,
            Country = PermAddCountry
        };
    }

    public Address GetCurrentAddress()
    {
        return new Address
        {
            Id = CurrAddAddressId,
            AddressLine1 = CurrAddAddressLine1,
            AddressLine2 = CurrAddAddressLine2,
            Barangay = CurrAddBarangay,
            City = CurrAddCity,
            Province = CurrAddProvince,
            ZipCode = CurrAddZipCode,
            Country = CurrAddCountry
        };
    }


    #region PermanentAddress

    public ObjectId? PermAddAddressId { get; set; }


    [Display(Name = "Unit/Lot/Street Name")]
    public string PermAddAddressLine1 { get; set; }

    [Display(Name = "Subdivision")] public string PermAddAddressLine2 { get; set; }

    [Display(Name = "Zone")] public string PermAddZone { get; set; }

    [Display(Name = "Barangay")] public string PermAddBarangay { get; set; }

    [Display(Name = "City")] public string PermAddCity { get; set; }

    [Display(Name = "Province")] public string PermAddProvince { get; set; }

    [Display(Name = "Zip Code")] public string PermAddZipCode { get; set; }

    [Display(Name = "Country")] public string PermAddCountry { get; set; }

    #endregion


    #region CurrentAddress

    public ObjectId CurrAddAddressId { get; set; }

    [Display(Name = "Unit/Lot/Street Name")]
    public string CurrAddAddressLine1 { get; set; }

    [Display(Name = "Subdivision")] public string CurrAddAddressLine2 { get; set; }

    [Display(Name = "Zone")] public string CurrAddZone { get; set; }

    [Display(Name = "Barangay")] public string CurrAddBarangay { get; set; }

    [Display(Name = "City")] public string CurrAddCity { get; set; }

    [Display(Name = "Province")] public string CurrAddProvince { get; set; }

    [Display(Name = "Zip Code")] public string CurrAddZipCode { get; set; }

    [Display(Name = "Country")] public string CurrAddCountry { get; set; }

    #endregion
}