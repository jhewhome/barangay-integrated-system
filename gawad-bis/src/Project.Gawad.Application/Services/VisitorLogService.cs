using AutoMapper;
using Microsoft.Extensions.Logging;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects.VisitorLog;

namespace Project.Gawad.Application.Services;

public class VisitorLogService : IVisitorLogService
{
    private readonly IVisitorLogRepository _visitorLogRepository;
    private readonly IMapper _mapper;
    private readonly ILogger<VisitorLogService> _logger;

    public VisitorLogService(IVisitorLogRepository visitorLogRepository, IMapper mapper, ILogger<VisitorLogService> logger)
    {
        _visitorLogRepository = visitorLogRepository ?? throw new ArgumentNullException(nameof(visitorLogRepository));
        _mapper = mapper ?? throw new ArgumentNullException(nameof(mapper));
        _logger = logger ?? throw new ArgumentNullException(nameof(logger));
    }

    public async Task<bool> AddAsync(VisitorLogObject log)
    {
        if (log == null)
            throw new ArgumentNullException(nameof(log));

        try
        {
            // Map to entity. Use AutoMapper when available; otherwise map required fields manually.
            VisitorLog entity;
            try
            {
                entity = _mapper.Map<VisitorLog>(log);
            }
            catch
            {
                entity = new VisitorLog
                {
                    FirstName = log.FirstName,
                    MiddleName = log.MiddleName,
                    LastName = log.LastName,
                    Purpose = log.Purpose,
                    CreatedDate = DateTime.UtcNow,
                    IsDeleted = false
                };
            }

            await _visitorLogRepository.AddAsync(entity);
            await ((Project.Gawad.Infrastructure.Repositories.VisitorLogRepository)_visitorLogRepository).SaveChangesAsync();

            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed to add visitor log");
            return false;
        }
    }
}