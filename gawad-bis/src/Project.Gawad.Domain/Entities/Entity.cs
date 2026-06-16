using MongoDB.Bson;
using MongoDB.Bson.Serialization.Attributes;

namespace Project.Gawad.Domain.Entities;

public abstract class Entity
{
    [BsonId]
    [BsonRepresentation(BsonType.ObjectId)]
    public ObjectId? Id { get; set; }

    [BsonRepresentation(BsonType.DateTime)]
    public DateTime CreatedDate { get; set; }

    [BsonRepresentation(BsonType.DateTime)]
    public DateTime? LastModifiedDate { get; set; }

    public ObjectId? CreatedById { get; set; }

    public ObjectId? LastModifiedById { get; set; }

    public bool IsDeleted { get; set; }
}