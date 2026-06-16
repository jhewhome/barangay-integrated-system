using AutoMapper;
using Project.Gawad.Application.Utils;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums;
using Project.Gawad.Domain.Objects.Resident;

namespace Project.Gawad.Application.Profiles.Residents;

public class ResidentToResidentProfileObjectMapping : Profile
{
    public ResidentToResidentProfileObjectMapping()
    {
        CreateMap<Resident, ResidentProfileObject>()
            .ForMember(dst => dst.Id,
                src => src.MapFrom(x => x.Id.ToString()))
            .ForMember(dst => dst.FullName,
                src => src.MapFrom(x =>
                    $"{x.Person.FirstName} {x.Person.MiddleName} {x.Person.LastName} {x.Person.Suffix}"))
            .ForMember(dst => dst.DateOfBirth,
                src => src.MapFrom(x => x.Person.DateOfBirth.ToLocalTime()))
            .ForMember(dst => dst.PlaceOfBirth,
                src => src.MapFrom(x => x.Person.PlaceOfBirth))
            .ForMember(dst => dst.Gender,
                src => src.MapFrom(x => x.Person.Gender))
            .ForMember(dst => dst.CivilStatus,
                src => src.MapFrom(x => x.Person.CivilStatus))
            .ForMember(dst => dst.SpouseName,
                src => src.MapFrom(x => x.Person.SpouseName))
            .ForMember(dst => dst.FatherName,
                src => src.MapFrom(x => x.Person.FatherName))
            .ForMember(dst => dst.MotherMaidenName,
                src => src.MapFrom(x => x.Person.MotherMaidenName))
            .ForMember(dst => dst.PermanentAddress,
                src => src.MapFrom(x => AddressHelper.GetAddress(x, AddressType.Permanent, false)))
            .ForMember(dst => dst.CurrentAddress,
                src => src.MapFrom(x => AddressHelper.GetAddress(x, AddressType.Current, false)))
            .ForMember(dst => dst.VoterId,
                src => src.MapFrom(x => x.VoterId))
            .ForMember(dst => dst.PrecintNo,
                src => src.MapFrom(x => x.PrecintNo))
            .ForMember(dst => dst.Age,
                src => src.MapFrom(x => CalculateAge(x.Person.DateOfBirth)))
            .ForMember(dst => dst.IsPWD,
                src => src.MapFrom(x => x.IsPWD))
            .ForMember(dst => dst.IsRegisteredVoter,
                src => src.MapFrom(x => x.IsRegisteredVoter))
            .ForMember(dst => dst.Nationality,
                src => src.MapFrom(x => x.Person.Nationality))
            ;
    }

    private string CalculateAge(DateTime? birthDate)
    {
        if (!birthDate.HasValue) return string.Empty;

        var years = DateTime.Now.Year - birthDate.Value.Year;
        var months = DateTime.Now.Month - birthDate.Value.Month;
        var days = DateTime.Now.Day - birthDate.Value.Day;

        // Adjust if the day difference is negative
        if (days < 0)
        {
            months--;
            var previousMonth = DateTime.Now.Month - 1 > 0 ? DateTime.Now.Month - 1 : 12;
            var daysInPreviousMonth = DateTime.DaysInMonth(DateTime.Now.Year, previousMonth);
            days += daysInPreviousMonth;
        }

        // Adjust if the month difference is negative
        if (months < 0)
        {
            years--;
            months += 12;
        }

        return $"{years} years, {months} months, {days} days";
    }
}