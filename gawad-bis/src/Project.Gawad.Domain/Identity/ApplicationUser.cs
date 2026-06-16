using AspNetCore.Identity.Mongo.Model;
using MongoDB.Bson.Serialization.Attributes;
using Project.Gawad.Domain.Enums;

namespace Project.Gawad.Domain.Identity;

[BsonIgnoreExtraElements]
public class ApplicationUser : MongoUser
{
    /// <summary>
    ///     Given name of the application user
    /// </summary>
    public string FirstName { get; set; }

    /// <summary>
    ///     Family name of the application user
    /// </summary>
    public string LastName { get; set; }

    public DateTime? CreatedDateTime { get; set; }

    public DateTime? LastModifiedDate { get; set; }

    public int? CreatedBy { get; set; }

    public int? LastModifiedBy { get; set; }

    public bool IsDeleted { get; set; }

    public RoleType RoleType { get; set; }
}