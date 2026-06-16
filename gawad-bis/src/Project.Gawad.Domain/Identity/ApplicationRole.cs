using AspNetCore.Identity.Mongo.Model;
using MongoDB.Bson.Serialization.Attributes;
using Project.Gawad.Domain.Enums;

namespace Project.Gawad.Domain.Identity;

[BsonIgnoreExtraElements]
public class ApplicationRole : MongoRole
{
    public RoleType Type { get; set; }
}