using Project.Gawad.Domain.Objects.VisitorLog;

namespace Project.Gawad.Core.Services;

public interface IVisitorLogService
{
    Task<bool> AddAsync(VisitorLogObject log);
}