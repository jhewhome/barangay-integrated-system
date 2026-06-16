using System.ComponentModel.DataAnnotations.Schema;
using MongoDB.Bson;
using Project.Gawad.Domain.Enums.BusinessPermits;

namespace Project.Gawad.Domain.Entities;

public class BusinessPermit : Entity
{
    [ForeignKey(nameof(PersonId))] public ObjectId PersonId { get; set; }

    public Person Person { get; set; }

    public string? BusinessName { get; set; }


    public string? Purpose { get; set; }

    public DateTime? IssuedDate { get; set; }

    public DateTime? ApplicationDate { get; set; }

    [ForeignKey(nameof(BarangayTrasactionId))]
    public ObjectId? BarangayTrasactionId { get; set; }

    public BarangayTrasaction BarangayTrasaction { get; set; }

    public BusinessType BusinessType { get; set; }

    #region PersonalAddress

    public string AddressLine1 { get; set; }

    public string? AddressLine2 { get; set; }

    public string? Zone { get; set; }

    public string Barangay { get; set; }

    public string City { get; set; }

    public string Province { get; set; }

    public string? ZipCode { get; set; }

    public string Country { get; set; }

    #endregion

    #region BusinessAddress

    public string BusinessAddressLine1 { get; set; }

    public string? BusinessAddressLine2 { get; set; }

    public string? BusinessZone { get; set; }

    public string BusinessBarangay { get; set; }

    public string BusinessCity { get; set; }

    public string BusinessProvince { get; set; }

    public string? BusinessZipCode { get; set; }

    public string BusinessCountry { get; set; }

    #endregion
}