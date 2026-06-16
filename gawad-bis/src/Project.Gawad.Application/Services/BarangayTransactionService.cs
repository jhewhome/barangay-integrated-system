using AutoMapper;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;

namespace Project.Gawad.Application.Services;

public class BarangayTransactionService(
    IBarangayTransactionRepository barangayTransactionRepository,
    IMapper mapper) : IBarangayTransactionService
{
    private readonly IBarangayTransactionRepository _barangayTransactionRepository =
        barangayTransactionRepository ?? throw new ArgumentNullException(nameof(barangayTransactionRepository));

    private readonly IMapper _mapper =
        mapper ?? throw new ArgumentNullException(nameof(mapper));

    public async Task<ServiceResponse<BarangayTrasaction>> DeleteTransactionAsync(string id,
        ApplicationUserObject deleteBy)
    {
        var transaction = await _barangayTransactionRepository.GetByIdAsync(ObjectId.Parse(id));

        if (transaction is null)
            throw new ApplicationException($"The transaction with id {id} was not found.");

        transaction.IsDeleted = true;
        transaction.LastModifiedDate = DateTime.UtcNow;
        transaction.LastModifiedById = deleteBy.Id;

        await _barangayTransactionRepository.UpdateAsync(transaction);
        await _barangayTransactionRepository.SaveChangesAsync();

        return new ServiceResponse<BarangayTrasaction>(transaction);
    }
}