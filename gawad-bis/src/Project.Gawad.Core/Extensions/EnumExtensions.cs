using System.ComponentModel.DataAnnotations;
using System.Reflection;

namespace Project.Gawad.Core.Extensions;

public static class EnumExtensions
{
    public static List<string> GetEnumValues<TEnum>(this object value) where TEnum : Enum
    {
        return Enum.GetValues(typeof(TEnum))
            .Cast<TEnum>()
            .Select(v => v.ToString())
            .ToList();
    }

    public static string GetEnumDisplayName(this Enum value)
    {
        var field = value.GetType().GetField(value.ToString());
        var attribute = field!.GetCustomAttribute<DisplayAttribute>();
        return attribute?.Name ?? value.ToString(); // Return display name or default enum name
    }
}