using System.ComponentModel.DataAnnotations.Schema;
using MongoDB.Bson;
using MongoDB.Bson.Serialization.Attributes;
using MongoDB.EntityFrameworkCore;

namespace Project.Gawad.Domain.Entities;

[Collection(nameof(Resident))]
public class Resident : Entity
{
    [BsonRepresentation(BsonType.DateTime)]
    public DateTime RegistratedDate { get; set; }

    public bool? IsRegisteredVoter { get; set; }

    public string? VoterId { get; set; }

    public string? PrecintNo { get; set; }

    // foreign keys and navigation
    public ObjectId? PersonId { get; set; }

    [ForeignKey(nameof(PersonId))] public Person? Person { get; set; }

    public bool? IsPWD { get; set; }

    public bool? IsSeniorCitizen { get; set; }


    public ICollection<Address> Addresses { get; set; }
}