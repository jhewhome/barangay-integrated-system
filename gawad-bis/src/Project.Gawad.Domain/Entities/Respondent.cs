using MongoDB.Bson;
using Project.Gawad.Domain.Enums;
using System.ComponentModel.DataAnnotations.Schema;

namespace Project.Gawad.Domain.Entities;

public class Respondent : Entity
{
    public int RespondentId { get; set; }

    [ForeignKey(nameof(PersonId))] public ObjectId PersonId { get; set; }

    public Person Person { get; set; } = new Person();

    public Address Address { get; set; } = new Address();

    // Added fields for complaint form
    public int? Age { get; set; }
    public Gender Gender { get; set; }
    public CivilStatus CivilStatus { get; set; }
    public string Occupation { get; set; } = string.Empty;
}