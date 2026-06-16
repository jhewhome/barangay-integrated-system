using System;
using System.Linq;
using AutoMapper;
using MongoDB.Bson;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.Complaint;

namespace Project.Gawad.Application.Profiles.Complaints
{
    public class FormToComplaintMapping : Profile
    {
        public FormToComplaintMapping()
        {
            CreateMap<ComplaintFormObject, Complaint>()
                // Map the existing ID or generate new one if empty
                .ForMember(d => d.Id, o => o.MapFrom(src => src.Id == ObjectId.Empty ? ObjectId.GenerateNewId() : src.Id))

                // Map top-level fields
                .ForMember(d => d.ComplaintType, o => o.MapFrom(src => src.ComplaintType))
                .ForMember(d => d.Subject, o => o.MapFrom(src => src.Subject))
                .ForMember(d => d.Details, o => o.MapFrom(src => src.Details))
                .ForMember(d => d.IncidentDateTime, o => o.MapFrom(src => src.IncidentDateTime))
                .ForMember(d => d.ReportedDate, o => o.MapFrom(src => src.ReportedDate))
                .ForMember(d => d.DateReceived, o => o.MapFrom(src => src.DateReceived))
                .ForMember(d => d.BlotterReceivedBy, o => o.MapFrom(src => src.BlotterReceivedBy))
                .ForMember(d => d.ORNumber, o => o.MapFrom(src => src.ORNumber))
                .ForMember(d => d.TotalFee, o => o.MapFrom(src => src.TotalFee))
                .ForMember(d => d.Note, o => o.MapFrom(src => src.Note))
                .ForMember(d => d.Recommendation, o => o.MapFrom(src => src.Recommendation))
                .ForMember(d => d.Status, o => o.MapFrom(src => src.Status))

                // Map nested collections
                .ForMember(d => d.Complainants,
                    o => o.MapFrom(src => src.Complainants.Select(c => new Complainant
                    {
                        Id = ObjectId.GenerateNewId(),
                        PersonId = c.PersonId,
                        Person = new Person
                        {
                            Id = ObjectId.GenerateNewId(),
                            FirstName = c.FirstName,
                            LastName = c.LastName,
                            MiddleName = c.MiddleName,
                            Suffix = c.Suffix,
                            DateOfBirth = c.DateOfBirth ?? default,
                            PlaceOfBirth = c.PlaceOfBirth,
                            Gender = c.Gender,
                            CivilStatus = c.CivilStatus,
                            SpouseName = c.SpouseName,
                            FatherName = c.FatherName,
                            MotherMaidenName = c.MotherMaidenName
                        }
                    }).ToList()))

                .ForMember(d => d.Respondents,
                    o => o.MapFrom(src => src.Respondents.Select(r => new Respondent
                    {
                        Id = ObjectId.GenerateNewId(),
                        PersonId = r.PersonId,
                        Person = new Person
                        {
                            Id = ObjectId.GenerateNewId(),
                            FirstName = r.FirstName,
                            LastName = r.LastName,
                            MiddleName = r.MiddleName
                        },
                        Age = r.Age,
                        Gender = r.Gender,
                        CivilStatus = r.CivilStatus,
                        Occupation = r.Occupation
                    }).ToList()));
        }
    }
}