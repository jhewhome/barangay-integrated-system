using System.Security.Claims;
using Microsoft.AspNetCore.Mvc.Rendering;

namespace Project.Gawad.Client.Extensions;

public static class ViewHelperExtensions
{
    public static string GetSpecificClaim(this ClaimsIdentity claimsIdentity, string claimType)
    {
        var claim = claimsIdentity.Claims.FirstOrDefault(x => x.Type == claimType);

        return claim != null ? claim.Value : string.Empty;
    }

    public static SelectList GetSelectListFromEnum<TEnum>(this object value, TEnum selected) where TEnum : Enum
    {
        var listItem = Enum.GetValues(typeof(TEnum))
            .Cast<TEnum>()
            .Select(v => v.ToString())
            .ToList();

        return new SelectList(listItem, selected?.ToString() ?? string.Empty);
    }
}