using MongoDB.Bson;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.Medicine;

namespace Project.Gawad.Core.Services;

public interface IMedicineService
{
    /// <summary>
    /// Create new medicine record in the database
    /// </summary>
    Task<ServiceResponse<CreateMedicineObject>> CreateMedicine(CreateMedicineObject createMedicineObject,
        ApplicationUserObject createdBy);

    /// <summary>
    /// Update medicine record in the database
    /// </summary>
    Task<ServiceResponse<UpdateMedicineObject>> UpdateMedicine(UpdateMedicineObject updateMedicineObject,
        ApplicationUserObject updatedBy);

    /// <summary>
    /// Soft-delete the medicine record in the database
    /// </summary>
    Task<bool> RemoveMedicine(string id, ApplicationUserObject deletedBy);

    /// <summary>
    /// Add stock to medicine inventory
    /// </summary>
    Task<ServiceResponse<CreateMedicineStockObject>> AddStock(CreateMedicineStockObject createStockObject,
        ApplicationUserObject createdBy);

    /// <summary>
    /// Create a medicine transaction (dispense, stock in, etc.)
    /// </summary>
    Task<ServiceResponse<CreateMedicineTransactionObject>> CreateTransaction(
        CreateMedicineTransactionObject createTransactionObject, ApplicationUserObject createdBy);

    /// <summary>
    /// Mark stock as notified for expiry
    /// </summary>
    Task<ServiceResponse<bool>> MarkStockAsNotified(ObjectId stockId, ApplicationUserObject notifiedBy);

    /// <summary>
    /// Record action taken on expired/expiring stock
    /// </summary>
    Task<ServiceResponse<bool>> RecordStockAction(RecordStockActionObject actionObject, ApplicationUserObject actionBy);

    /// <summary>
    /// Update a medicine transaction (only for StockIn transactions)
    /// </summary>
    Task<ServiceResponse<UpdateMedicineTransactionObject>> UpdateTransaction(
        UpdateMedicineTransactionObject updateTransactionObject, ApplicationUserObject updatedBy);

    /// <summary>
    /// Delete a medicine transaction (admin only - reverses stock changes)
    /// </summary>
    Task<bool> DeleteTransaction(string transactionId, ApplicationUserObject deletedBy);

    /// <summary>
    /// Soft-delete a medicine stock record (admin only)
    /// </summary>
    Task<bool> RemoveStock(string stockId, ApplicationUserObject deletedBy);
}




