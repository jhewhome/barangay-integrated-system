namespace Project.Gawad.Domain.Objects.UserManagement;

public class ChangePasswordObject
{
    public string CurrentPassword { get; set; }

    public string NewPassword { get; set; }

    public string ConfirmNewPassword { get; set; }
}