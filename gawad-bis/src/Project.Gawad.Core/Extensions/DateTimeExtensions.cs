using System.Globalization;

namespace Project.Gawad.Core.Extensions;

public static class DateTimeExtensions
{
    public static string FormatDateWithOrdinal(this DateTime date)
    {
        if (date == default) return string.Empty;

        var day = date.Day;
        var suffix = GetOrdinalSuffix(day);
        return $"{day}{suffix} day of {date.ToString("MMMM yyyy", CultureInfo.InvariantCulture)}";
    }

    private static string GetOrdinalSuffix(int day)
    {
        if (day >= 11 && day <= 13) return "th"; // Special case for 11-13

        return (day % 10) switch
        {
            1 => "st",
            2 => "nd",
            3 => "rd",
            _ => "th"
        };
    }

    public static DateTime StartOfWeek(this DateTime date, DayOfWeek startOfWeek = DayOfWeek.Sunday)
    {
        int diff = (7 + (date.DayOfWeek - startOfWeek)) % 7;
        return date.AddDays(-1 * diff).Date;
    }

    public static DateTime EndOfWeek(this DateTime date, DayOfWeek startOfWeek = DayOfWeek.Sunday)
    {
        return date.StartOfWeek(startOfWeek).AddDays(6).Date.AddDays(1).AddTicks(-1);
    }

    public static DateTime StartOfMonth(this DateTime date)
    {
        return new DateTime(date.Year, date.Month, 1).Date;
    }

    public static DateTime EndOfMonth(this DateTime date)
    {
        return new DateTime(date.Year, date.Month, DateTime.DaysInMonth(date.Year, date.Month)).Date.AddDays(1).AddTicks(-1);
    }
}