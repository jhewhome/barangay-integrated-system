using MongoDB.Bson;
using MongoDB.Bson.Serialization.Attributes;
using Project.Gawad.Domain.Enums;

namespace Project.Gawad.Domain.Entities;

public class Person : Entity
{
    public string FirstName { get; set; } = string.Empty;

    public string LastName { get; set; } = string.Empty;

    public string? MiddleName { get; set; }

    public string? Suffix { get; set; }

    [BsonRepresentation(BsonType.DateTime)]
    public DateTime DateOfBirth { get; set; }

    public string? PlaceOfBirth { get; set; }

    public Gender Gender { get; set; }

    public CivilStatus CivilStatus { get; set; }

    public string? SpouseName { get; set; }

    public string? FatherName { get; set; }

    public string? MotherMaidenName { get; set; }

    public string? Nationality { get; set; }

    public bool IsResident { get; set; }
}