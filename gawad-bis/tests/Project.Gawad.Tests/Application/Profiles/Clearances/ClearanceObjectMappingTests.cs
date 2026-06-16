using AutoMapper;
using MongoDB.Bson;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums;
using Project.Gawad.Domain.Enums.Clearances;
using Project.Gawad.Domain.Objects.Clearance;
using Xunit;

namespace Project.Gawad.Tests.Application.Profiles.Clearances;

[Collection("AutoMapper collection")]
public class ClearanceObjectMappingTests(AutoMapperProfileFixture fixture)
{
    private readonly IMapper _mapper = fixture.Mapper;

    [Fact]
    public void ClearanceObjectToClearance_MapCorrectly_To_Clearance()
    {
        var clearanceFormObject = new ClearanceFormObject
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
            AddressId = ObjectId.GenerateNewId(),
            AddressLine1 = "123 Main Street",
            AddressLine2 = "",
            Zone = "Zone 1",
            Barangay = "Barangay 563",
            City = "Manila City",
            Province = "Metro Manila",
            ZipCode = "1000",
            Country = "Philippines",
            ClearancePurpose = ClearancePurpose.LocalEmployment
        };

        var mappedClearance = _mapper.Map<ClearanceFormObject, Clearance>(clearanceFormObject);

        Assert.NotNull(mappedClearance);
        Assert.Multiple(() =>
        {
            Assert.Equal(mappedClearance.ClearancePurpose, clearanceFormObject.ClearancePurpose);
        });
    }
}