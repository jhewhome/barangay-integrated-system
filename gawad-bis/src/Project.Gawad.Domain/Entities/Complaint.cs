using System.ComponentModel.DataAnnotations.Schema;
using MongoDB.Bson;
using Project.Gawad.Domain.Enums.Complaints;

namespace Project.Gawad.Domain.Entities;

public class Complaint : Entity
{
    public ICollection<Complainant> Complainants { get; set; } = new List<Complainant>();

    public ICollection<Respondent> Respondents { get; set; } = new List<Respondent>();

    public ComplaintType ComplaintType { get; set; }

    public string Subject { get; set; } = string.Empty;

    public string? Details { get; set; }

    public DateTime? IncidentDateTime { get; set; }

    public DateTime? ReportedDate { get; set; }

    public DateTime? DateReceived { get; set; }

    public string? BlotterReceivedBy { get; set; }

    public string? ORNumber { get; set; }

    public decimal? TotalFee { get; set; }

    public string? Note { get; set; }

    public string? Recommendation { get; set; }

    public ComplaintStatus Status { get; set; }
}