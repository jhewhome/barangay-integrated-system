using MongoDB.Bson;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Integration;
using Project.Gawad.Domain.Objects.Medicine;
using Project.Gawad.Domain.Enums.Medicine;

namespace Project.Gawad.Core.Providers;

public interface IMedicineProvider
{
    Task<UpdateMedicineObject?> GetCreateUpdateMedicineObjectAsync(ObjectId medicineId);

    Task<PaginatedRecords<MedicineListObject>> GetMedicinesListAsync(int page = 1, int itemsPerPage = 10,
        int sortColIndex = 0, string sortColDir = "asc", string? search = null, 
        MedicineCategory? category = null, UnitOfMeasure? unitType = null, string? status = null);

    Task<MedicineDetailObject?> GetMedicineDetailObjectAsync(ObjectId medicineId);

    Task<PaginatedRecords<MedicineStockListObject>> GetMedicineStocksListAsync(ObjectId medicineId,
        int page = 1, int itemsPerPage = 10);

    Task<PaginatedRecords<MedicineTransactionListObject>> GetMedicineTransactionsListAsync(
        ObjectId? medicineId = null,
        int page = 1,
        int itemsPerPage = 10,
        string? search = null,
        MedicineTransactionType? transactionType = null,
        string? recipientName = null,
        DateTime? startDate = null,
        DateTime? endDate = null,
        ObjectId? createdByUserId = null);

    Task<IEnumerable<MedicineListObject>> GetLowStockMedicinesAsync();
    
    Task<IEnumerable<MedicineStockListObject>> GetExpiringStocksAsync(int days = 30);
    
    Task<IEnumerable<MedicineStockListObject>> GetExpiredStocksAsync();

    Task<IEnumerable<MedicineStockStatusReportObject>> GetStockStatusReportAsync(ReportFilterObject? filter = null);

    Task<IEnumerable<MedicineUsageReportObject>> GetUsageReportAsync(ReportFilterObject filter);

    Task<IEnumerable<MedicineSpendingLogObject>> GetSpendingLogAsync(ReportFilterObject filter);

    Task<MedicineBalanceSummaryObject> GetBalanceSummaryAsync(ReportFilterObject filter);

    Task<IEnumerable<MedicineTransactionListObject>> GetDispensedLogAsync(ReportFilterObject filter);

    Task<MedicineTransactionListObject?> GetTransactionListObjectByIdAsync(ObjectId id);

    Task<PaginatedRecords<AuditLogListObject>> GetAuditLogsAsync(AuditLogFilterObject filter, int page = 1, int itemsPerPage = 50);

    Task<IEnumerable<GawadMedicineIntegrationDto>> GetMedicinesIntegrationExportAsync();
}

