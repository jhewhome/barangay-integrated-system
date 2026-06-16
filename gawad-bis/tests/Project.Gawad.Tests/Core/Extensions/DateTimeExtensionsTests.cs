using Project.Gawad.Core.Extensions;
using Xunit;

namespace Project.Gawad.Tests.Core.Extensions;

public class DateTimeExtensionsTests
{
    [Fact]
    public void FormatDateWithOrdinal_ShouldReturnFormatedDate()
    {
        var date = new DateTime(2024, 12, 10);
        
        Assert.Equal("10th day of December 2024", date.FormatDateWithOrdinal());
    }
    
    [Theory]
    [InlineData(2025, 1, 3, "3rd day of January 2025")]
    [InlineData(2024, 4, 22, "22nd day of April 2024")]
    [InlineData(2024, 10, 1, "1st day of October 2024")]
    [InlineData(2024, 5, 2, "2nd day of May 2024")]
    [InlineData(2024, 8, 8, "8th day of August 2024")]
    [InlineData(2024, 3, 31, "31st day of March 2024")]
    public void FormatDateWithOrdinal_ShouldReturnFormatedDate_BasedOnParam(int year, int month, int day, 
        string expectedFormatting)
    {
        var date = new DateTime(year, month, day);
        Assert.Equal(expectedFormatting, date.FormatDateWithOrdinal());
    }
}