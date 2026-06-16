using MongoDB.Bson;
using System;
using System.Collections.Generic;
using System.ComponentModel.DataAnnotations;
using Project.Gawad.Domain.Enums.Complaints;

namespace Project.Gawad.Domain.Objects.Complaint
{
    public class ComplaintFormObject
    {
        public ObjectId Id { get; set; }

        public ObjectId PersonId { get; set; }

        public string ContactNumber { get; set; } = string.Empty;

        [Display(Name = "Complainants")]
        public List<ComplainantObject> Complainants { get; set; } = new List<ComplainantObject>();

        [Display(Name = "Respondents")]
        public List<RespondentObject> Respondents { get; set; } = new List<RespondentObject>();

        [Required(ErrorMessage = "Complaint Type is required")]
        [Display(Name = "Complaint Type")]
        public ComplaintType ComplaintType { get; set; }

        [Required(ErrorMessage = "Subject is required")]
        [Display(Name = "Subject")]
        public string Subject { get; set; } = string.Empty;

        [Display(Name = "Details")]
        public string? Details { get; set; }

        [Display(Name = "Incident Date & Time")]
        public DateTime? IncidentDateTime { get; set; }

        [Display(Name = "Reported Date")]
        public DateTime? ReportedDate { get; set; }

        [Display(Name = "Date Received")]
        public DateTime? DateReceived { get; set; }

        [Display(Name = "Blotter Received By")]
        public string? BlotterReceivedBy { get; set; }

        [Display(Name = "O.R. Number")]
        public string? ORNumber { get; set; }

        [Display(Name = "Total Fee")]
        public decimal? TotalFee { get; set; }

        [Display(Name = "Note")]
        public string? Note { get; set; }

        [Display(Name = "Recommendation")]
        public string? Recommendation { get; set; }

        [Display(Name = "Status")]
        public ComplaintStatus Status { get; set; }
    }
}
