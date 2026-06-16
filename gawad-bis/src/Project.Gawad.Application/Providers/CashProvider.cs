using AutoMapper;
using MongoDB.Bson;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Cash;
using Project.Gawad.Domain.Enums.Cash;
using Project.Gawad.Domain.Enums.Sales;

namespace Project.Gawad.Application.Providers;

public class CashProvider(
    ICashSessionRepository cashSessionRepository,
    ICashMovementRepository cashMovementRepository,
    ISaleRepository saleRepository,
    IPaymentRepository paymentRepository,
    IMapper mapper) : ICashProvider
{
    private readonly IMapper _mapper = mapper ?? throw new ArgumentNullException(nameof(mapper));
    private readonly ICashSessionRepository _cashSessionRepository = cashSessionRepository ?? throw new ArgumentNullException(nameof(cashSessionRepository));
    private readonly ICashMovementRepository _cashMovementRepository = cashMovementRepository ?? throw new ArgumentNullException(nameof(cashMovementRepository));
    private readonly ISaleRepository _saleRepository = saleRepository ?? throw new ArgumentNullException(nameof(saleRepository));
    private readonly IPaymentRepository _paymentRepository = paymentRepository ?? throw new ArgumentNullException(nameof(paymentRepository));

    public async Task<CashSessionDetailObject?> GetOpenCashSessionAsync()
    {
        var session = await _cashSessionRepository.GetOpenSessionAsync();
        if (session == null)
            return null;

        return await GetCashSessionDetailObjectAsync(session.Id!.Value);
    }

    public async Task<CashSessionDetailObject?> GetCashSessionDetailObjectAsync(ObjectId sessionId)
    {
        var session = await _cashSessionRepository.GetSessionWithMovementsAsync(sessionId);
        if (session == null || session.IsDeleted)
            return null;

        var sales = await _saleRepository.GetSalesByCashSessionIdAsync(sessionId);
        var payments = await _paymentRepository.GetPaymentsByCashSessionIdAsync(sessionId);
        var movements = await _cashMovementRepository.GetMovementsBySessionIdAsync(sessionId);

        var detailObj = _mapper.Map<CashSessionDetailObject>(session);
        detailObj.TotalSales = sales.Where(s => s.Status == SaleStatus.Completed).Sum(s => s.TotalAmount);
        detailObj.TotalPayments = payments.Sum(p => p.Amount);
        detailObj.SaleCount = sales.Count();
        detailObj.Movements = movements.Select(m => _mapper.Map<CashMovementListObject>(m)).ToList();

        return detailObj;
    }

    public async Task<PaginatedRecords<CashSessionListObject>> GetCashSessionsListAsync(
        int page = 1, int itemsPerPage = 10,
        DateTime? startDate = null, DateTime? endDate = null)
    {
        IEnumerable<CashSession> sessions;

        if (startDate.HasValue && endDate.HasValue)
        {
            sessions = await _cashSessionRepository.GetSessionsByDateRangeAsync(startDate.Value, endDate.Value);
        }
        else
        {
            sessions = await _cashSessionRepository.GetAllAsync();
        }

        var totalRecords = sessions.Count();
        var paginatedData = sessions
            .OrderByDescending(s => s.SessionDate)
            .Skip((page - 1) * itemsPerPage)
            .Take(itemsPerPage)
            .ToList();

        var sessionListObjects = new List<CashSessionListObject>();

        foreach (var session in paginatedData)
        {
            var sales = await _saleRepository.GetSalesByCashSessionIdAsync(session.Id!.Value);
            var payments = await _paymentRepository.GetPaymentsByCashSessionIdAsync(session.Id!.Value);

            var sessionObj = _mapper.Map<CashSessionListObject>(session);
            sessionObj.TotalSales = sales.Where(s => s.Status == SaleStatus.Completed).Sum(s => s.TotalAmount);
            sessionObj.TotalPayments = payments.Sum(p => p.Amount);
            sessionListObjects.Add(sessionObj);
        }

        return new PaginatedRecords<CashSessionListObject>
        {
            PageNumber = page,
            PageSize = itemsPerPage,
            RecordsTotal = totalRecords,
            RecordsFiltered = totalRecords,
            Data = sessionListObjects
        };
    }
}



