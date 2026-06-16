using System.Linq.Expressions;
using AutoMapper;
using Microsoft.Extensions.Caching.Memory;
using MongoDB.Bson;
using Project.Gawad.Core.Extensions;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums;
using Project.Gawad.Domain.Enums.Clearances;
using Project.Gawad.Domain.Enums.Transactions;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Transactions;

namespace Project.Gawad.Application.Providers;

public class BarangayTransactionProvider(
    IBarangayTransactionRepository barangayTransactionRepository,
    IClearanceRepository clearanceRepository,
    IPersonRepository personRepository,
    IResidentsRepository residentsRepository,
    IBusinessPermitRepository businessPermitRepository,
    IMemoryCache memoryCache,
    IMapper mapper) : IBarangayTransactionProvider
{
    private readonly IBarangayTransactionRepository _barangayTransactionRepository =
        barangayTransactionRepository ?? throw new ArgumentException(nameof(barangayTransactionRepository));

    private readonly IBusinessPermitRepository _businessPermitRepository =
        businessPermitRepository ?? throw new ArgumentException(nameof(businessPermitRepository));

    private readonly IClearanceRepository _clearanceRepository =
        clearanceRepository ?? throw new ArgumentException(nameof(clearanceRepository));

    private readonly IMapper _mapper =
        mapper ?? throw new ArgumentException(nameof(mapper));

    private readonly IMemoryCache _memoryCache =
        memoryCache ?? throw new ArgumentException(nameof(memoryCache));

    private readonly IPersonRepository _personRepository =
        personRepository ?? throw new ArgumentException(nameof(personRepository));

    private readonly IResidentsRepository _residentRepository =
        residentsRepository ?? throw new ArgumentException(nameof(residentsRepository));

    public async Task<PaginatedRecords<TransactionListObject>> GetTransactionsListAsync(int page = 1,
        int itemsPerPage = 10,
        int sortColIndex = 0, string sortColDir = "asc", string? search = null)
    {
        var isAscending = sortColDir.Equals("asc", StringComparison.InvariantCultureIgnoreCase);

        Expression<Func<BarangayTrasaction, object>> order = sortColIndex switch
        {
            0 => a => a.CreatedDate,
            2 => a => a.ControlNumber,
            3 => a => a.Type,
            4 => a => a.Fee,
            5 => a => a.CreatedDate,
            _ => order = a => a.CreatedDate
        };

        Func<BarangayTrasaction, bool>? filter = string.IsNullOrEmpty(search)
            ? null
            : new Func<BarangayTrasaction, bool>(a =>
                !a.IsDeleted &&
                a.ControlNumber.ToLower().Contains(search?.ToLower())
            );

        Func<BarangayTrasaction, BarangayTrasaction>? select = r => r;

        var paginatedResult = await _barangayTransactionRepository.GetPaginatedRecordsAsync(page, itemsPerPage,
            isAscending, select, order, filter);

        var transactionList = new List<TransactionListObject>();
        foreach (var transaction in paginatedResult.Data)
        {
            var person = await _personRepository.GetByIdAsync(transaction.PersonId);
            if (person is not null)
                transactionList.Add(new TransactionListObject
                {
                    Id = transaction.Id.ToString()!,
                    ControlNumber = transaction.ControlNumber,
                    RequesterName = $"{person.FirstName} {person.LastName}",
                    TransactionType = transaction.Type.GetEnumDisplayName(),
                    PaidAmount = $"{transaction.Fee:N2}",
                    CreatedOn = $"{transaction.CreatedDate.ToLocalTime():MMMM dd, yyyy HH:mm:ss}"
                });
        }

        return new PaginatedRecords<TransactionListObject>
        {
            PageNumber = page,
            PageSize = itemsPerPage,
            RecordsTotal = paginatedResult.RecordsTotal,
            RecordsFiltered = paginatedResult.RecordsFiltered,
            Data = transactionList
        };
    }

    public async Task<TransactionDetailsObject> GetTransactionDetailObjectAsync(ObjectId transactionId)
    {
        var transaction = await _barangayTransactionRepository.GetByIdAsync(transactionId);

        var person = await _personRepository.GetByIdAsync(transaction!.PersonId);

        var resident = await _residentRepository.GetResidentByPersonIdAsync(person.Id.Value);

        return new TransactionDetailsObject
        {
            Id = transaction.Id.ToString()!,
            ControlNumber = transaction.ControlNumber,
            FullName = person is not null ? $"{person!.FirstName} {person.LastName}" : string.Empty,
            PersonId = person.Id?.ToString() ?? string.Empty,
            ResidentId = resident?.Id?.ToString() ?? string.Empty,
            TransactionType = transaction.Type,
            PaidAmount = transaction.Fee,
            TransactionDateTime = transaction.CreatedDate.ToLocalTime(),
            Notes = transaction.Notes,
            OfficerOfTheDay = transaction.OfficerOfTheDay
        };
    }

    public async Task<PaginatedRecords<TransactionListObject>> GetResidentTransactionsListAsync(string residentId,
        int page = 1, int itemsPerPage = 10, int sortColIndex = 0,
        string sortColDir = "asc", string? search = null)
    {
        var isAscending = sortColDir.Equals("desc", StringComparison.InvariantCultureIgnoreCase);

        Expression<Func<BarangayTrasaction, object>> order = sortColIndex switch
        {
            0 => a => a.ControlNumber,
            2 => a => a.ControlNumber,
            3 => a => a.Type,
            4 => a => a.Fee,
            5 => a => a.CreatedDate,
            _ => order = a => a.ControlNumber
        };

        var resident = await _residentRepository.GetByIdAsync(ObjectId.Parse(residentId));

        Func<BarangayTrasaction, bool>? filter = string.IsNullOrEmpty(search)
            ? null
            : new Func<BarangayTrasaction, bool>(a =>
                !a.IsDeleted &&
                a.PersonId == resident.PersonId &&
                a.ControlNumber.ToLower().Contains(search?.ToLower())
            );

        Func<BarangayTrasaction, BarangayTrasaction>? select = r => r;

        var paginatedResult =
            await _barangayTransactionRepository.GetResidentTransactionPaginatedRecordsAsync(residentId, page,
                itemsPerPage,
                isAscending, select, order, filter);

        var transactionList = new List<TransactionListObject>();
        foreach (var transaction in paginatedResult.Data)
        {
            var person = await _personRepository.GetByIdAsync(transaction.PersonId);
            if (person is not null)
                transactionList.Add(new TransactionListObject
                {
                    Id = transaction.Id.ToString()!,
                    ControlNumber = transaction.ControlNumber,
                    RequesterName = $"{person.FirstName} {person.LastName}",
                    TransactionType = transaction.Type.GetEnumDisplayName(),
                    PaidAmount = $"{transaction.Fee:N2}",
                    CreatedOn = $"{transaction.CreatedDate.ToLocalTime():MMMM dd, yyyy HH:mm:ss}"
                });
        }

        return new PaginatedRecords<TransactionListObject>
        {
            PageNumber = page,
            PageSize = itemsPerPage,
            RecordsTotal = paginatedResult.RecordsTotal,
            RecordsFiltered = paginatedResult.RecordsFiltered,
            Data = transactionList
        };
    }

    public async Task<PaginatedRecords<TransactionListObject>> GetRecentTransactionsListAsync(int lastNDays,
        int page = 1, int itemsPerPage = 10, int sortColIndex = 0,
        string sortColDir = "asc", string? search = null)
    {
        var isAscending = sortColDir.Equals("desc", StringComparison.InvariantCultureIgnoreCase);

        Expression<Func<BarangayTrasaction, object>> order = sortColIndex switch
        {
            0 => a => a.ControlNumber,
            2 => a => a.ControlNumber,
            3 => a => a.Type,
            4 => a => a.Fee,
            5 => a => a.CreatedDate,
            _ => order = a => a.ControlNumber
        };

        Func<BarangayTrasaction, bool>? filter = string.IsNullOrEmpty(search)
            ? null
            : new Func<BarangayTrasaction, bool>(a =>
                !a.IsDeleted &&
                a.ControlNumber.ToLower().Contains(search?.ToLower())
            );

        Func<BarangayTrasaction, BarangayTrasaction>? select = r => r;

        var paginatedResult =
            await _barangayTransactionRepository.GetMostRecentTransactionPaginatedRecordsAsync(lastNDays, page,
                itemsPerPage,
                isAscending, select, order, filter);

        var transactionList = new List<TransactionListObject>();
        foreach (var transaction in paginatedResult.Data)
        {
            var person = await _personRepository.GetByIdAsync(transaction.PersonId);
            if (person is not null)
                transactionList.Add(new TransactionListObject
                {
                    Id = transaction.Id.ToString()!,
                    ControlNumber = transaction.ControlNumber,
                    RequesterName = $"{person.FirstName} {person.LastName}",
                    TransactionType = transaction.Type.GetEnumDisplayName(),
                    PaidAmount = $"{transaction.Fee:N2}",
                    CreatedOn = $"{transaction.CreatedDate.ToLocalTime():MMMM dd, yyyy HH:mm:ss}"
                });
        }

        return new PaginatedRecords<TransactionListObject>
        {
            PageNumber = page,
            PageSize = itemsPerPage,
            RecordsTotal = paginatedResult.RecordsTotal,
            RecordsFiltered = paginatedResult.RecordsFiltered,
            Data = transactionList
        };
    }

    public async Task<BarangayDocumentType> GetDocumetTypeAsync(ObjectId transactionId)
    {
        var transaction = await _barangayTransactionRepository.GetByIdAsync(transactionId);

        return transaction.Type switch
        {
            TransactionType.BarangayClearance => BarangayDocumentType.BarangayClearance,
            TransactionType.BusinessPermit => BarangayDocumentType.BusinessPermit
        };
    }

    public async Task<IDictionary<string, string>> GetTransactionDocumentDetailsAsync(ObjectId transactionId)
    {
        Dictionary<string, string> documentData = new();

        var cacheKey = $"transDocDetails_{transactionId.ToString()}";

        if (_memoryCache.TryGetValue(cacheKey, out documentData))
            return documentData;

        var transaction = await _barangayTransactionRepository.GetByIdAsync(transactionId);

        if (transaction is null)
            return new Dictionary<string, string>();

        documentData = transaction.Type switch
        {
            TransactionType.BarangayClearance => await GetClearanceDocumentDataAsync(transaction),
            TransactionType.BusinessPermit => await GetBusinessDocumentDataAsync(transaction),
            _ => new Dictionary<string, string>()
        };

        _memoryCache.Set(cacheKey, documentData, new MemoryCacheEntryOptions
        {
            AbsoluteExpirationRelativeToNow = TimeSpan.FromMinutes(10),
            SlidingExpiration = TimeSpan.FromMinutes(8)
        });

        return documentData;
    }

    private async Task<Dictionary<string, string>> GetClearanceDocumentDataAsync(BarangayTrasaction transaction)
    {
        var clearanceDocumentData = new Dictionary<string, string>();

        var clearance = await _clearanceRepository.GetClearanceByBarangayTransactionIdAsync(transaction.Id);

        var person = await _personRepository.GetByIdAsync(transaction!.PersonId);

        var fullName = string.IsNullOrEmpty(person.MiddleName?.Trim())
            ? $"{person.FirstName} {person.LastName}"
            : $"{person.FirstName} {person?.MiddleName?.ToUpper()[0]}. {person.LastName}";

        clearanceDocumentData.Add("{transactionId}", transaction.Id.ToString()!);
        clearanceDocumentData.Add("{fullName}", fullName?.ToUpper());
        clearanceDocumentData.Add("{nationality}", person.Nationality);
        clearanceDocumentData.Add("{address}",
            $"{clearance.AddressLine1?.ToUpper()},  {clearance.Barangay?.ToUpper()},  {clearance.City?.ToUpper()}");

        var purpose = clearance.ClearancePurpose is null ||
                      clearance.ClearancePurpose is ClearancePurpose.OthersPurposes
            ? clearance.Purpose?.ToUpper()
            : clearance.ClearancePurpose?.GetEnumDisplayName()?.ToUpper();

        clearanceDocumentData.Add("{purpose}", purpose);

        var applicationDate = clearance.ApplicationDate is not null
            ? clearance.ApplicationDate.Value.ToLocalTime().FormatDateWithOrdinal()
            : string.Empty;

        clearanceDocumentData.Add("{issuedDate}", applicationDate);

        clearanceDocumentData.Add("{receiptNumber}", transaction.ReceiptNumber?.ToUpper());

        var officer = string.IsNullOrEmpty(transaction.OfficerOfTheDay?.Trim())
            ? "____________________________"
            : transaction.OfficerOfTheDay?.ToUpper();
        clearanceDocumentData.Add("{officerOftheDay}", officer);


        return clearanceDocumentData;
    }

    private async Task<Dictionary<string, string>> GetBusinessDocumentDataAsync(BarangayTrasaction transaction)
    {
        var businessPermitDocumentData = new Dictionary<string, string>();

        var businessPermit =
            await _businessPermitRepository.GetBusinessPermitByBarangayTransactionIdAsync(transaction.Id);

        var person = await _personRepository.GetByIdAsync(businessPermit!.PersonId);

        var fullName = string.IsNullOrEmpty(person.MiddleName?.Trim())
            ? $"{person.FirstName} {person.LastName}"
            : $"{person.FirstName} {person?.MiddleName?.ToUpper()[0]}. {person.LastName}";

        businessPermitDocumentData.Add("{transactionId}", transaction.Id.ToString()!);
        businessPermitDocumentData.Add("{fullName}", fullName?.ToUpper());
        businessPermitDocumentData.Add("{nationality}", person.Nationality);
        businessPermitDocumentData.Add("{address}",
            $"{businessPermit.AddressLine1?.ToUpper()}, {businessPermit.Barangay?.ToUpper()}, {businessPermit.City?.ToUpper()}");
        businessPermitDocumentData.Add("{businessAddress}",
            $"{businessPermit.BusinessAddressLine1?.ToUpper()}, {businessPermit.BusinessBarangay?.ToUpper()}, {businessPermit.City?.ToUpper()}");


        var applicationDate = businessPermit.ApplicationDate is not null
            ? businessPermit.ApplicationDate.Value.ToLocalTime().FormatDateWithOrdinal()
            : string.Empty;

        businessPermitDocumentData.Add("{issuedDate}", applicationDate);
        businessPermitDocumentData.Add("{businessType}", businessPermit.BusinessType.GetEnumDisplayName()?.ToUpper());
        businessPermitDocumentData.Add("{businessName}", businessPermit.BusinessName?.ToUpper());
        businessPermitDocumentData.Add("{receiptNumber}", transaction.ReceiptNumber?.ToUpper());

        var officer = string.IsNullOrEmpty(transaction.OfficerOfTheDay?.Trim())
            ? "____________________________"
            : transaction.OfficerOfTheDay?.ToUpper();
        businessPermitDocumentData.Add("{officerOftheDay}", officer);

        return businessPermitDocumentData;
    }
}