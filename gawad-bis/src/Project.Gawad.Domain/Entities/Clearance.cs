using System.ComponentModel.DataAnnotations.Schema;
using MongoDB.Bson;
using Project.Gawad.Domain.Enums.Clearances;

namespace Project.Gawad.Domain.Entities;

public class Clearance : Entity
{
    [ForeignKey(nameof(PersonId))] public ObjectId PersonId { get; set; }

    public Person Person { get; set; }

    public string AddressLine1 { get; set; }

    public string? AddressLine2 { get; set; }

    public string? Zone { get; set; }

    public string Barangay { get; set; }

    public string City { get; set; }

    public string Province { get; set; }

    public string? ZipCode { get; set; }

    public string Country { get; set; }

    public string? Purpose { get; set; }

    public DateTime? IssuedDate { get; set; }

    public DateTime? ApplicationDate { get; set; }

    [ForeignKey(nameof(BarangayTrasactionId))]
    public ObjectId? BarangayTrasactionId { get; set; }

    public BarangayTrasaction BarangayTrasaction { get; set; }

    public ClearancePurpose? ClearancePurpose { get; set; }
}