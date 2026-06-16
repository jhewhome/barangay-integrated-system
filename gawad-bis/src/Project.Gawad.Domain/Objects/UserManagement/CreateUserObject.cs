using System.ComponentModel.DataAnnotations;
using MongoDB.Bson;
using Project.Gawad.Domain.Enums;

namespace Project.Gawad.Domain.Objects.UserManagement;

public class CreateUserObject
{
    public ObjectId? Id { get; set; }

    [Display(Name = "First Name")] public string Firstname { get; set; }

    [Display(Name = "Middle Name")] public string Middlename { get; set; }

    [Display(Name = "Last Name")] public string Lastname { get; set; }

    [Display(Name = "Username")] public string Username { get; set; }


    [Display(Name = "Password")] public string Password { get; set; }

    [Display(Name = "Role")] public RoleType Role { get; set; }
}