using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums;

namespace Project.Gawad.Application.Utils;

public static class AddressHelper
{
    public static string GetAddress(Resident resident, 
        AddressType addressType = AddressType.Current,
        bool isShortAddress = true)
    {
        var address = resident.Addresses.FirstOrDefault(a => a.Type == addressType);

        if (address is not null)
        {
            if (isShortAddress)
                return $"{address.AddressLine1}, {address.Barangay}, {address.City}";

            return $"{address.AddressLine1}, {address.Barangay}, {address.City}, {address.Province}, {address.Country}";
        }


        return string.Empty;
    }
    
    public static string GetAddressValue(Resident resident, 
        AddressValue addressValue,
        AddressType addressType = AddressType.Current)
    {
        var address = resident.Addresses.FirstOrDefault(a => a.Type == addressType);

        if (address is not null)
            return addressValue switch
            {
                AddressValue.AddressLine1 => address.AddressLine1,
                AddressValue.AddressLine2 => address.AddressLine2,
                AddressValue.Zone => address.Zone,
                AddressValue.Barangay => address.Barangay,
                AddressValue.City => address.City,
                AddressValue.Province => address.Province,
                AddressValue.Country => address.Country,
                AddressValue.ZipCode => address.ZipCode,
            };

        return string.Empty;
    }
}