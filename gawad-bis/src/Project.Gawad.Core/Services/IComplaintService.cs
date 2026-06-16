using Project.Gawad.Core;
using Project.Gawad.Domain.Enums.Complaints;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.Complaint;
using System.Threading.Tasks;

namespace Project.Gawad.Core.Services
{
    public interface IComplaintService
    {

        Task<ServiceResponse<ComplaintFormObject>> ApplyComplaint(
            ComplaintFormObject complaintFormObject,
            ApplicationUserObject createdBy);
        Task ApplyComplaintAsync(ComplaintFormObject dto, ApplicationUserObject currentUser);
        Task<ServiceResponse<bool>> ChangeComplaintStatus(
            string complaintId,
            ComplaintStatus newStatus,
            ApplicationUserObject updatedBy);
        Task GetComplaintListAsync();
    }
}