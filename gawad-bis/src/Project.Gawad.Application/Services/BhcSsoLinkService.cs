using System.Security.Cryptography;
using System.Text;
using System.Text.Json;
using Microsoft.Extensions.Options;
using Project.Gawad.Application.Options;
using Project.Gawad.Core.Services;

namespace Project.Gawad.Application.Services;

public class BhcSsoLinkService : IBhcSsoLinkService
{
    private readonly BhcIntegrationOption _options;

    public BhcSsoLinkService(IOptions<BhcIntegrationOption> options)
    {
        _options = options.Value;
    }

    public string? BuildLink(string relativePath, string? username, string? role = null)
    {
        var direct = _options.BuildUrl(relativePath);
        if (direct is null)
        {
            return null;
        }

        if (!_options.SsoEnabled
            || string.IsNullOrWhiteSpace(username)
            || string.IsNullOrWhiteSpace(_options.SsoSecret))
        {
            return direct;
        }

        var returnPath = relativePath.StartsWith('/') ? relativePath : "/" + relativePath;
        var token = CreateToken(username, role);
        if (token is null)
        {
            return direct;
        }

        var query = "token=" + Uri.EscapeDataString(token)
                    + "&return=" + Uri.EscapeDataString(returnPath);

        return _options.BuildUrl("/auth/gawad?" + query);
    }

    public string? BuildRegisterPatientLink(string residentId, string? username, string? role = null)
    {
        if (string.IsNullOrWhiteSpace(residentId))
        {
            return null;
        }

        var path = "/patients/create?gawad_resident_id=" + Uri.EscapeDataString(residentId);
        return BuildLink(path, username, role);
    }

    private string? CreateToken(string username, string? role)
    {
        var lifetime = _options.SsoTokenLifetimeSeconds <= 0 ? 300 : _options.SsoTokenLifetimeSeconds;
        var payload = new Dictionary<string, object>
        {
            ["u"] = username,
            ["exp"] = DateTimeOffset.UtcNow.AddSeconds(lifetime).ToUnixTimeSeconds(),
            ["n"] = Guid.NewGuid().ToString("N")
        };

        if (!string.IsNullOrWhiteSpace(role))
        {
            payload["r"] = role;
        }

        var payloadJson = JsonSerializer.Serialize(payload);
        var payloadB64 = Base64UrlEncode(Encoding.UTF8.GetBytes(payloadJson));
        var secret = _options.SsoSecret ?? string.Empty;

        using var hmac = new HMACSHA256(Encoding.UTF8.GetBytes(secret));
        var signature = Base64UrlEncode(hmac.ComputeHash(Encoding.UTF8.GetBytes(payloadB64)));

        return payloadB64 + "." + signature;
    }

    private static string Base64UrlEncode(byte[] data)
    {
        return Convert.ToBase64String(data).TrimEnd('=').Replace('+', '-').Replace('/', '_');
    }
}
