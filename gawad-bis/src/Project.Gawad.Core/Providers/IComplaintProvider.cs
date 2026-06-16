using MongoDB.Bson;
using Project.Gawad.Domain.Enums.Complaints;
using Project.Gawad.Domain.Objects.Complaint;
using System.Collections.Generic;
using System.Threading.Tasks;

namespace Project.Gawad.Core.Providers
{
    public interface IComplaintProvider
    {
        Task<IEnumerable<ComplaintListObject>> GetComplaintListAsync();

        Task<ComplaintFormObject?> GetComplaintForEditAsync(string id);

        // Return the persisted ObjectId for created/updated complaint
        Task<ObjectId> CreateOrUpdateAsync(ComplaintFormObject dto);

        Task ChangeComplaintStatusAsync(string id, ComplaintStatus newStatus);

        Task DeleteAsync(string id);
    }
}
