using AutoMapper;
using Microsoft.Extensions.Logging;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums.Cash;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.Cash;

namespace Project.Gawad.Application.Services;

public class CashService(
    ICashSessionRepository cashSessionRepository,
    ICashMovementRepository cashMovementRepository,
    ISaleRepository saleRepository,
    IPaymentRepository paymentRepository,
    IMapper mapper,
    ILogger<CashService> logger) : ICashService
{
    private readonly ICashSessionRepository _cashSessionRepository = cashSessionRepository ?? throw new ArgumentNullException(nameof(cashSessionRepository));
    private readonly ICashMovementRepository _cashMovementRepository = cashMovementRepository ?? throw new ArgumentNullException(nameof(cashMovementRepository));
    private readonly ISaleRepository _saleRepository = saleRepository ?? throw new ArgumentNullException(nameof(saleRepository));
    private readonly IPaymentRepository _paymentRepository = paymentRepository ?? throw new ArgumentNullException(nameof(paymentRepository));
    private readonly IMapper _mapper = mapper ?? throw new ArgumentNullException(nameof(mapper));
    private readonly ILogger<CashService> _logger = logger ?? throw new ArgumentNullException(nameof(logger));

    public async Task<ServiceResponse<CreateCashSessionObject>> OpenCashSession(
        CreateCashSessionObject createSessionObject,
        ApplicationUserObject openedBy)
    {
        // Check if there's already an open session
        var openSession = await _cashSessionRepository.GetOpenSessionAsync();
        if (openSession != null)
        {
            var response = new ServiceResponse<CreateCashSessionObject>();
            response.AddModelError("", "There is already an open cash session.");
            return response;
        }

        var session = new CashSession
        {
            Id = ObjectId.GenerateNewId(),
            SessionDate = createSessionObject.SessionDate,
            Status = CashSessionStatus.Open,
            OpenedById = openedBy.Id.ToString(),
            OpenedAt = DateTime.Now,
            OpeningFloat = createSessionObject.OpeningFloat,
            CreatedDate = DateTime.Now,
            CreatedById = openedBy.Id
        };

        await _cashSessionRepository.AddAsync(session);
        await _cashSessionRepository.SaveChangesAsync();

        // Create opening movement
        var openingMovement = new CashMovement
        {
            Id = ObjectId.GenerateNewId(),
            CashSessionId = session.Id!.Value,
            MovementType = CashMovementType.Opening,
            Amount = createSessionObject.OpeningFloat,
            MovementDate = DateTime.Now,
            Notes = "Opening float",
            CreatedDate = DateTime.Now,
            CreatedById = openedBy.Id
        };

        await _cashMovementRepository.AddAsync(openingMovement);
        await _cashMovementRepository.SaveChangesAsync();

        createSessionObject.Notes = createSessionObject.Notes; // Keep for reference
        return new ServiceResponse<CreateCashSessionObject>(createSessionObject);
    }

    public async Task<ServiceResponse<CloseCashSessionObject>> CloseCashSession(
        CloseCashSessionObject closeSessionObject,
        ApplicationUserObject closedBy)
    {
        if (!ObjectId.TryParse(closeSessionObject.SessionId, out var sessionId))
        {
            var response = new ServiceResponse<CloseCashSessionObject>();
            response.AddModelError("SessionId", "Invalid session ID.");
            return response;
        }

        var session = await _cashSessionRepository.GetByIdAsync(sessionId);
        if (session == null || session.IsDeleted)
        {
            var response = new ServiceResponse<CloseCashSessionObject>();
            response.AddModelError("SessionId", "Session not found.");
            return response;
        }

        if (session.Status == CashSessionStatus.Closed)
        {
            var response = new ServiceResponse<CloseCashSessionObject>();
            response.AddModelError("SessionId", "Session is already closed.");
            return response;
        }

        // Calculate totals from sales and payments
        var sales = await _saleRepository.GetSalesByCashSessionIdAsync(sessionId);
        var payments = await _paymentRepository.GetPaymentsByCashSessionIdAsync(sessionId);

        var totalSales = sales.Where(s => s.Status == Domain.Enums.Sales.SaleStatus.Completed)
            .Sum(s => s.TotalAmount);
        var totalPayments = payments.Sum(p => p.Amount);

        var expectedAmount = session.OpeningFloat + totalPayments;
        var variance = closeSessionObject.ClosingAmount - expectedAmount;

        // Update session
        session.Status = CashSessionStatus.Closed;
        session.ClosedById = closedBy.Id.ToString();
        session.ClosedAt = DateTime.Now;
        session.ClosingAmount = closeSessionObject.ClosingAmount;
        session.ExpectedAmount = expectedAmount;
        session.Variance = variance;
        session.ClosingNotes = closeSessionObject.ClosingNotes;
        session.LastModifiedDate = DateTime.Now;
        session.LastModifiedById = closedBy.Id;

        await _cashSessionRepository.UpdateAsync(session);
        await _cashSessionRepository.SaveChangesAsync();

        // Create closing movement
        var closingMovement = new CashMovement
        {
            Id = ObjectId.GenerateNewId(),
            CashSessionId = sessionId,
            MovementType = CashMovementType.Closing,
            Amount = closeSessionObject.ClosingAmount,
            MovementDate = DateTime.Now,
            Notes = $"Closing: Expected {expectedAmount}, Actual {closeSessionObject.ClosingAmount}, Variance {variance}",
            Reason = closeSessionObject.ClosingNotes,
            CreatedDate = DateTime.Now,
            CreatedById = closedBy.Id
        };

        await _cashMovementRepository.AddAsync(closingMovement);
        await _cashMovementRepository.SaveChangesAsync();

        return new ServiceResponse<CloseCashSessionObject>(closeSessionObject);
    }

    public async Task<bool> AddCashMovement(string sessionId, decimal amount, string movementType, string? reason, ApplicationUserObject createdBy)
    {
        if (!ObjectId.TryParse(sessionId, out var sessionIdObj))
            return false;

        var session = await _cashSessionRepository.GetByIdAsync(sessionIdObj);
        if (session == null || session.IsDeleted || session.Status == CashSessionStatus.Closed)
            return false;

        if (!Enum.TryParse<CashMovementType>(movementType, out var movementTypeEnum))
            return false;

        var movement = new CashMovement
        {
            Id = ObjectId.GenerateNewId(),
            CashSessionId = sessionIdObj,
            MovementType = movementTypeEnum,
            Amount = amount,
            MovementDate = DateTime.Now,
            Reason = reason,
            CreatedDate = DateTime.Now,
            CreatedById = createdBy.Id
        };

        await _cashMovementRepository.AddAsync(movement);
        await _cashMovementRepository.SaveChangesAsync();

        return true;
    }
}
