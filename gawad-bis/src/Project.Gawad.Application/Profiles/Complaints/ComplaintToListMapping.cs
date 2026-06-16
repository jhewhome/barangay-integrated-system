using System;
using System.Linq;
using AutoMapper;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Complaint;

namespace Project.Gawad.Application.Profiles.Complaints
{
    public class ComplaintToListMapping : Profile
    {
        public ComplaintToListMapping()
        {
            CreateMap<Complaint, ComplaintListObject>()
                // Convert ObjectId to string
                .ForMember(d => d.Id,
                    o => o.MapFrom(s => s.Id.ToString()))
                // Use the first complainant's full name
                .ForMember(d => d.ComplainantName,
                    o => o.MapFrom(s =>
                        s.Complainants != null && s.Complainants.Any() && s.Complainants.First().Person != null
                            ? $"{s.Complainants.First().Person.LastName}, {s.Complainants.First().Person.FirstName}"
                            : "Unknown Complainant"))
                // Map enums
                .ForMember(d => d.Type,
                    o => o.MapFrom(s => s.ComplaintType))
                .ForMember(d => d.Status,
                    o => o.MapFrom(s => s.Status))
                // Safe default for date
                .ForMember(d => d.IncidentDate,
                    o => o.MapFrom(s => s.IncidentDateTime ?? DateTime.MinValue));
        }
    }
}