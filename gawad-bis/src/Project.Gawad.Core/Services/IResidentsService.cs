using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.Resident;

namespace Project.Gawad.Core.Services;

public interface IResidentsService
{
    /// <summary>
    /// Create new resident record in the database
    /// </summary>
    /// <param name="createResident"></param>
    /// <param name="createdBy"></param>
    /// <returns></returns>
    Task<ServiceResponse<CreateResidentObject>> CreateResident(CreateResidentObject createResident,
        ApplicationUserObject createdBy);

    /// <summary>
    /// Update resident record in the database
    /// </summary>
    /// <param name="updateResidentObject"></param>
    /// <param name="updatedBy"></param>
    /// <returns></returns>
    Task<ServiceResponse<UpdateResidentObject>> UpdateResident(UpdateResidentObject updateResidentObject,
        ApplicationUserObject updatedBy);

    /// <summary>
    /// Soft-delete the resident record in the database
    /// </summary>
    /// <param name="id"></param>
    /// <param name="deletedBy"></param>
    /// <returns></returns>
    Task<bool> RemoveResident(string id, ApplicationUserObject deletedBy);
}