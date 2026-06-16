using System.ComponentModel.DataAnnotations.Schema;
using MongoDB.Bson;

namespace Project.Gawad.Domain.Entities;

public class Complainant : Entity
{
    [ForeignKey(nameof(PersonId))] public ObjectId PersonId { get; set; }

    public Person Person { get; set; } = new Person();
}