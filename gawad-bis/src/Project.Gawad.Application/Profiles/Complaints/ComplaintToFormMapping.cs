using System;
using System.Linq;
using AutoMapper;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Complaint;

namespace Project.Gawad.Application.Profiles.Complaints
{
    public class ComplaintToFormMapping : Profile
    {
        public ComplaintToFormMapping()
        {
            CreateMap<Complaint, ComplaintFormObject>()
                .ForMember(d => d.Id,
                    o => o.MapFrom(s => s.Id.GetValueOrDefault()))
                .ForMember(d => d.IncidentDateTime,
                    o => o.MapFrom(s => s.IncidentDateTime))
                .ForMember(d => d.ComplaintType,
                    o => o.MapFrom(s => s.ComplaintType))
                .ForMember(d => d.Subject,
                    o => o.MapFrom(s => s.Subject))
                .ForMember(d => d.Details,
                    o => o.MapFrom(s => s.Details))
                .ForMember(d => d.ReportedDate,
                    o => o.MapFrom(s => s.ReportedDate))
                .ForMember(d => d.Status,
                    o => o.MapFrom(s => s.Status))
                .ForMember(d => d.Complainants,
                    o => o.MapFrom(s => s.Complainants))
                .ForMember(d => d.Respondents,
                    o => o.MapFrom(s => s.Respondents));
        }
    }
}