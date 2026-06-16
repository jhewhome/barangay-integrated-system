using System.ComponentModel.DataAnnotations;

namespace Project.Gawad.Domain.ViewModels.Login;

public class LoginViewModel
{
    public LoginViewModel()
    {
        Username = string.Empty;
        Password = string.Empty;
        KeepMeLogin = false;
    }

    public LoginViewModel(string username, string password, bool keepMeLogin)
    {
        Username = username;
        Password = password;
        KeepMeLogin = keepMeLogin;
    }

    [Display(Name = "Username")] public string Username { get; set; }

    [Display(Name = "Password")] public string Password { get; set; }

    [Display(Name = "Keep Me Signed In")] public bool KeepMeLogin { get; set; }
}