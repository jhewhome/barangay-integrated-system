using AutoMapper;
using Microsoft.Extensions.Logging;
using MongoDB.Bson;
using Project.Gawad.Application.Providers;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Enums.Complaints;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.Complaint;
using System;
using System.Threading.Tasks;

namespace Project.Gawad.Application.Services
{
    public class ComplaintService : IComplaintService
    {
        private readonly IComplaintProvider _provider;
        private readonly ILogger<ComplaintService> _logger;
        private readonly IMapper _mapper;

        public ComplaintService(
            IComplaintProvider complaintProvider,
            ILogger<ComplaintService> logger,
            IMapper mapper)
        {
            _provider = complaintProvider;
            _logger = logger;
            _mapper = mapper;
        }

        public Task<ServiceResponse<ComplaintFormObject>> ApplyComplaint(ComplaintFormObject complaintFormObject, ApplicationUserObject createdBy)
        {
            throw new NotImplementedException();
        }

        public async Task<ServiceResponse<ComplaintFormObject>> ApplyComplaintAsync(
            ComplaintFormObject dto,
            ApplicationUserObject createdBy)
        {
            try
            {
                await _provider.CreateOrUpdateAsync(dto);
                // success: wrap and return the DTO
                return new ServiceResponse<ComplaintFormObject>(dto);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error applying complaint");
                var resp = new ServiceResponse<ComplaintFormObject>();
                resp.AddModelError(string.Empty, ex.Message);
                return resp;
            }
        }

        public Task<ServiceResponse<bool>> ChangeComplaintStatus(string complaintId, ComplaintStatus newStatus, ApplicationUserObject updatedBy)
        {
            throw new NotImplementedException();
        }

        public async Task<ServiceResponse<bool>> ChangeComplaintStatusAsync(
            string complaintId,
            ComplaintStatus newStatus,
            ApplicationUserObject updatedBy)
        {
            try
            {
                await _provider.ChangeComplaintStatusAsync(complaintId, newStatus);
                // success: return true wrapped
                return new ServiceResponse<bool>(true);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error changing complaint status");
                var resp = new ServiceResponse<bool>();
                resp.AddModelError(string.Empty, ex.Message);
                return resp;
            }
        }

        public Task GetComplaintListAsync()
        {
            throw new NotImplementedException();
        }

        Task IComplaintService.ApplyComplaintAsync(ComplaintFormObject dto, ApplicationUserObject currentUser)
        {
            return ApplyComplaintAsync(dto, currentUser);
        }
    }
}