using AutoMapper;
using MongoDB.Bson;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums;
using Project.Gawad.Domain.Objects.Resident;
using Xunit;

namespace Project.Gawad.Tests.Application.Profiles.Residents;

[Collection("AutoMapper collection")]
public class CreateResidentObjectToResidentMappingTests(AutoMapperProfileFixture fixture)
{
    private readonly IMapper _mapper = fixture.Mapper;

    [Fact]
    public void CreateResidentObject_MapsCorrectly_To_Resident()
    {
        var createResidentObject = new CreateResidentObject
        {
            FirstName = "Jose Protacio",
            MiddleName = "Alonso Realonda",
            LastName = "Rizal Mercado",
            Suffix = "",
            DateOfBirth = new DateTime(1980, 01, 01),
            PlaceOfBirth = "Binan, Laguna",
            Gender = Gender.Male,
            CivilStatus = CivilStatus.Married,
            SpouseName = "Josephine Bracken",
            FatherName = "Francisco Rizal Mercado",
            MotherMaidenName = "Teodora Alonso Realonda",
            VoterId = "ID-12345-0",
            PrecintNo = "1277422",
            PermAddAddressId = ObjectId.GenerateNewId(),
            PermAddAddressLine1 = "123 Main Street",
            PermAddAddressLine2 = "",
            PermAddZone = "Zone 1",
            PermAddBarangay = "Barangay 563",
            PermAddCity = "Manila City",
            PermAddProvince = "Metro Manila",
            PermAddZipCode = "1000",
            PermAddCountry = "Philippines",
            CurrAddAddressId = ObjectId.GenerateNewId(),
            CurrAddAddressLine1 = "123 Main Street",
            CurrAddAddressLine2 = "",
            CurrAddZone = "Zone 1",
            CurrAddBarangay = "Barangay 563",
            CurrAddCity = "Manila City",
            CurrAddProvince = "Metro Manila",
            CurrAddZipCode = "1000",
            CurrAddCountry = "Philippines"
        };

        var resident = new Resident();
        var mappedResident = _mapper.Map(createResidentObject, resident);
        
        Assert.NotNull(mappedResident);
        Assert.Multiple(() =>
        {
            Assert.Equal(mappedResident.Person.FirstName, createResidentObject.FirstName);
            Assert.Equal(mappedResident.Person.MiddleName, createResidentObject.MiddleName);
            Assert.Equal(mappedResident.Person.LastName, createResidentObject.LastName);
            Assert.Equal(mappedResident.Person.Gender, createResidentObject.Gender);
            Assert.Equal(mappedResident.Person.CivilStatus, createResidentObject.CivilStatus);
            Assert.Equal(mappedResident.RegistratedDate.Date, DateTime.UtcNow.Date);
        });
    }
}