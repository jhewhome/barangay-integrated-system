using System;
using System.Linq;
using System.ComponentModel.DataAnnotations;
using MongoDB.Bson;
using Project.Gawad.Domain.Enums;

namespace Project.Gawad.Domain.Objects.Complaint;

public class RespondentObject
{
    public ObjectId? Id { get; set; }
    public ObjectId PersonId { get; set; }

    [Display(Name = "Given Name")] public string FirstName { get; set; } = string.Empty;
    [Display(Name = "Family Name")] public string LastName { get; set; } = string.Empty;
    [Display(Name = "Middle Name")] public string? MiddleName { get; set; }

    public string FullName
        => string.Join(" ",
            new[] {
                  LastName,
                  FirstName,
                  string.IsNullOrWhiteSpace(MiddleName) ? null : MiddleName
            }
            .Where(s => !string.IsNullOrWhiteSpace(s)));

    [Display(Name = "Address")] public string Address { get; set; } = string.Empty;
    [Display(Name = "Age")] public int? Age { get; set; }
    [Display(Name = "Gender")] public Gender Gender { get; set; }
    [Display(Name = "Civil Status")] public CivilStatus CivilStatus { get; set; }
    [Display(Name = "Occupation")] public string Occupation { get; set; } = string.Empty;
}