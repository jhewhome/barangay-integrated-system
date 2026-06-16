using Project.Gawad.Domain.Enums;

namespace Project.Gawad.Domain.Entities;

public class Address : Entity
{
    public string AddressLine1 { get; set; } = string.Empty;

    public string? AddressLine2 { get; set; }

    public string? Zone { get; set; }

    public string Barangay { get; set; } = string.Empty;

    public string City { get; set; } = string.Empty;

    public string Province { get; set; } = string.Empty;

    public string? ZipCode { get; set; }

    public string Country { get; set; } = string.Empty;

    public AddressType Type { get; set; }
}