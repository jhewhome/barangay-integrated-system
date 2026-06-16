namespace Project.Gawad.Application.Options;

public class VerificationUrlOption
{
    /// <summary>
    /// Base URL for QR code verification links (e.g., http://192.168.1.100:5001 or https://your-domain.com)
    /// Leave empty to use relative URLs
    /// </summary>
    public string? BaseUrl { get; set; }
}





