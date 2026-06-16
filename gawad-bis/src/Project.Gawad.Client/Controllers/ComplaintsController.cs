using System;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Authorization;
using MongoDB.Bson;
using Project.Gawad.Core.Providers;
using Project.Gawad.Domain.Objects.Complaint;
using Project.Gawad.Domain.Enums;
using System.Collections.Generic;
using System.Threading.Tasks;

namespace Project.Gawad.Client.Controllers
{
    public class ComplaintsController : Controller
    {
        private readonly IComplaintProvider _complaintProvider;
        private readonly IResidentsProvider _residentsProvider;

        public ComplaintsController(
            IComplaintProvider complaintProvider,
            IResidentsProvider residentsProvider)
        {
            _complaintProvider = complaintProvider ?? throw new ArgumentNullException(nameof(complaintProvider));
            _residentsProvider = residentsProvider ?? throw new ArgumentNullException(nameof(residentsProvider));
        }

        // GET: /Complaints
        [HttpGet]
        public IActionResult Index()
        {
            return View();
        }

        // GET: /Complaints/GetComplaintsList
        [HttpGet]
        [AllowAnonymous]
        public async Task<IActionResult> GetComplaintsList(int page = 1, int itemsPerPage = 0, int sortColIndex = 0,
            string sortColDir = "asc", string? search = null)
        {
            try
            {
                var complaints = await _complaintProvider.GetComplaintListAsync();
                Console.WriteLine($"Found {complaints?.Count() ?? 0} complaints");
                
                // Convert to DataTable format with safe property access
                var data = complaints?.Select(c => new
                {
                    id = c?.Id?.ToString() ?? "",
                    complainantName = c?.ComplainantName ?? "Unknown",
                    type = c?.Type.ToString() ?? "Unknown",
                    status = c?.Status.ToString() ?? "Unknown",
                    incidentDate = c?.IncidentDate.ToString("yyyy-MM-dd") ?? DateTime.Now.ToString("yyyy-MM-dd")
                }).Cast<object>().ToList() ?? new List<object>();

                return Ok(new
                {
                    data = data,
                    recordsTotal = data.Count,
                    recordsFiltered = data.Count
                });
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Error loading complaints: {ex.Message}");
                // Return empty data structure instead of error
                return Ok(new
                {
                    data = new List<object>(),
                    recordsTotal = 0,
                    recordsFiltered = 0
                });
            }
        }

        // GET: /Complaints/FileComplaint
        [HttpGet]
        public IActionResult FileComplaint()
        {
            var model = new ComplaintFormObject
            {
                Complainants = new List<ComplainantObject> { 
                    new ComplainantObject 
                    { 
                        PersonId = ObjectId.Empty,
                        CompmAddCountry = "Philippines",
                        FirstName = "",
                        LastName = "",
                        DateOfBirth = DateTime.Now.AddYears(-25),
                        Gender = Project.Gawad.Domain.Enums.Gender.Male,
                        CivilStatus = Project.Gawad.Domain.Enums.CivilStatus.Single,
                        CompAddressLine1 = "",
                        CompAddBarangay = "",
                        CompAddCity = "",
                        CompAddProvince = ""
                    } 
                },
                Respondents = new List<RespondentObject> { 
                    new RespondentObject 
                    { 
                        PersonId = ObjectId.Empty,
                        FirstName = "",
                        LastName = "",
                        Address = "",
                        Occupation = "",
                        Gender = Project.Gawad.Domain.Enums.Gender.Male,
                        CivilStatus = Project.Gawad.Domain.Enums.CivilStatus.Single,
                        Age = 25
                    } 
                },
                Subject = "",
                Details = "",
                IncidentDateTime = DateTime.Now,
                ReportedDate = DateTime.Now,
                ComplaintType = Project.Gawad.Domain.Enums.Complaints.ComplaintType.Others,
                Status = Project.Gawad.Domain.Enums.Complaints.ComplaintStatus.Active
            };
            return View(model);
        }

        // POST: /Complaints/FileComplaint
        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> FileComplaint(ComplaintFormObject model)
        {
            // Debug: Log received model data
            Console.WriteLine($"Received model - Subject: '{model.Subject}'");
            Console.WriteLine($"Received model - Complainants count: {model.Complainants?.Count ?? 0}");
            Console.WriteLine($"Received model - Respondents count: {model.Respondents?.Count ?? 0}");
            
            if (model.Complainants?.Count > 0)
            {
                Console.WriteLine($"Complainant[0] - FirstName: '{model.Complainants[0].FirstName}', LastName: '{model.Complainants[0].LastName}'");
            }
            
            if (model.Respondents?.Count > 0)
            {
                Console.WriteLine($"Respondent[0] - FirstName: '{model.Respondents[0].FirstName}', LastName: '{model.Respondents[0].LastName}'");
            }

            // Debug: Log all form data
            Console.WriteLine("=== FORM DATA DEBUG ===");
            foreach (var key in Request.Form.Keys)
            {
                Console.WriteLine($"Form Key: {key}, Value: '{Request.Form[key]}'");
            }
            Console.WriteLine("=== END FORM DATA DEBUG ===");

            // Debug: Log model state
            if (!ModelState.IsValid)
            {
                // Log validation errors
                foreach (var error in ModelState)
                {
                    if (error.Value.Errors.Count > 0)
                    {
                        Console.WriteLine($"Validation Error for {error.Key}: {string.Join(", ", error.Value.Errors.Select(e => e.ErrorMessage))}");
                    }
                }
                return View(model);
            }

            try
            {
                // create or update and get persisted id directly from provider
                var persistedId = await _complaintProvider.CreateOrUpdateAsync(model);

                if (persistedId == ObjectId.Empty)
                {
                    // fallback: show index with message if persistence failed
                    TempData["Error"] = "Unable to save complaint. Please try again.";
                    return RedirectToAction(nameof(Index));
                }

                TempData["Success"] = "Complaint submitted successfully!";
                return RedirectToAction(nameof(Details), new { id = persistedId.ToString() });
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Error saving complaint: {ex.Message}");
                TempData["Error"] = $"Error saving complaint: {ex.Message}";
                return View(model);
            }
        }

        // Backwards-compatible route: /Complaints/ComplaintDetails/{id}
        [HttpGet("Complaints/ComplaintDetails/{id?}")]
        public async Task<IActionResult> ComplaintDetails(string id)
        {
            // forward to the canonical Details action
            return await Details(id);
        }

        // GET: /Complaints/Details/{id}
        [HttpGet]
        public async Task<IActionResult> Details(string id)
        {
            var model = await _complaintProvider.GetComplaintForEditAsync(id);
            if (model == null)
                return NotFound();

            return View("ComplaintDetails", model);
        }

        // GET: /Complaints/UpdateComplaint/{id}
        [HttpGet]
        public async Task<IActionResult> UpdateComplaint(string id)
        {
            var model = await _complaintProvider.GetComplaintForEditAsync(id);
            if (model == null)
                return NotFound();

            return View("FileComplaint", model);
        }

        // POST: /Complaints/UpdateComplaint/{id}
        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> UpdateComplaint(string id, ComplaintFormObject model)
        {
            if (!ModelState.IsValid)
            {
                return View("FileComplaint", model);
            }

            try
            {
                var persistedId = await _complaintProvider.CreateOrUpdateAsync(model);
                if (persistedId == ObjectId.Empty)
                {
                    TempData["Error"] = "Unable to update complaint. Please try again.";
                    return RedirectToAction(nameof(Index));
                }

                TempData["Success"] = "Complaint updated successfully!";
                return RedirectToAction(nameof(Details), new { id = persistedId.ToString() });
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Error updating complaint: {ex.Message}");
                TempData["Error"] = $"Error updating complaint: {ex.Message}";
                return View("FileComplaint", model);
            }
        }

        // GET: /Complaints/EditStatus/{id}
        [HttpGet]
        public async Task<IActionResult> EditStatus(string id)
        {
            var model = await _complaintProvider.GetComplaintForEditAsync(id);
            if (model == null)
                return NotFound();

            return View(model);
        }

        // POST: /Complaints/EditStatus/{id}
        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> EditStatus([FromRoute] string id, [FromForm] int newStatus)
        {
            try
            {
                Console.WriteLine($"EditStatus POST - ID: {id}, NewStatus: {newStatus}");
                Console.WriteLine($"Request.Form keys: {string.Join(", ", Request.Form.Keys)}");
                Console.WriteLine($"Request.Form count: {Request.Form.Count}");
                
                var statusEnum = (Project.Gawad.Domain.Enums.Complaints.ComplaintStatus)newStatus;
                Console.WriteLine($"Converted to enum: {statusEnum}");
                
                await _complaintProvider.ChangeComplaintStatusAsync(id, statusEnum);
                Console.WriteLine("Status change completed successfully");
                
                TempData["Success"] = "Complaint status updated successfully!";
                return RedirectToAction(nameof(Index));
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Error in EditStatus POST: {ex.Message}");
                Console.WriteLine($"Stack trace: {ex.StackTrace}");
                TempData["Error"] = $"Error updating status: {ex.Message}";
                return RedirectToAction(nameof(Index));
            }
        }

        // GET: /Complaints/GetComplaintDetails/{id}
        [HttpGet]
        [AllowAnonymous]
        public async Task<IActionResult> GetComplaintDetails(string id)
        {
            try
            {
                if (string.IsNullOrEmpty(id))
                {
                    return Json(new { success = false, message = "Complaint ID is required" });
                }

                var complaint = await _complaintProvider.GetComplaintForEditAsync(id);
                if (complaint == null)
                {
                    return Json(new { success = false, message = "Complaint not found" });
                }

                return Json(new { success = true, data = complaint });
            }
            catch (Exception ex)
            {
                return Json(new { success = false, message = ex.Message });
            }
        }

        // POST: /Complaints/Delete/{id}
        [HttpPost]
        [AllowAnonymous]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Delete(string id)
        {
            try
            {
                if (string.IsNullOrEmpty(id))
                {
                    return Json(new { success = false, message = "Complaint ID is required" });
                }

                await _complaintProvider.DeleteAsync(id);
                return Json(new { success = true, message = "Complaint deleted successfully" });
            }
            catch (Exception ex)
            {
                return Json(new { success = false, message = ex.Message });
            }
        }

        // JSON endpoint: /Complaints/GetResidentDetails?residentId={id}
        [HttpGet("Complaints/GetResidentDetails")]
        public async Task<IActionResult> GetResidentDetails(ObjectId residentId)
        {
            var resident = await _residentsProvider.GetResidentProfileObjectAsync(residentId);
            if (resident == null)
                return NotFound();

            return Json(new
            {
                fullName = resident.FullName,
                contactNumber = resident.ContactNumber,
                address = resident.CurrentAddress,
                personId = resident.Id
            });
        }
    }
}
