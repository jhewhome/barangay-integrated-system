using AutoMapper;
using MongoDB.Bson;
using Project.Gawad.Application.Services;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums.Complaints;
using Project.Gawad.Domain.Objects.Complaint;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;

namespace Project.Gawad.Application.Providers
{
    public class ComplaintProvider : IComplaintProvider
    {
        private readonly IComplaintRepository _repo;
        private readonly IPersonRepository _personRepo;
        private readonly IMapper _mapper;

        public ComplaintProvider(IComplaintRepository repo, IPersonRepository personRepo, IMapper mapper)
        {
            _repo = repo ?? throw new ArgumentNullException(nameof(repo));
            _personRepo = personRepo ?? throw new ArgumentNullException(nameof(personRepo));
            _mapper = mapper ?? throw new ArgumentNullException(nameof(mapper));
        }

        public async Task<IEnumerable<ComplaintListObject>> GetComplaintListAsync()
        {
            var entities = await _repo.GetAllAsync();
            // Exclude soft-deleted complaints
            entities = entities == null
                ? Array.Empty<Complaint>()
                : entities.Where(c => c.IsDeleted == false).ToList();
            
            // Manually load Person data for each complainant
            foreach (var complaint in entities)
            {
                foreach (var complainant in complaint.Complainants)
                {
                    if (complainant.Person == null || string.IsNullOrEmpty(complainant.Person.FirstName))
                    {
                        var person = await _personRepo.GetByIdAsync(complainant.PersonId);
                        if (person != null)
                        {
                            complainant.Person = person;
                        }
                    }
                }
            }
            
            return _mapper.Map<IEnumerable<ComplaintListObject>>(entities);
        }

        public async Task<ComplaintFormObject?> GetComplaintForEditAsync(string id)
        {
            if (!ObjectId.TryParse(id, out var oid)) return null;
            var entity = await _repo.GetByIdAsync(oid);
            if (entity == null) return null;
            
            // Manually load Person data for complainants and respondents
            foreach (var complainant in entity.Complainants)
            {
                if (complainant.Person == null || string.IsNullOrEmpty(complainant.Person.FirstName))
                {
                    var person = await _personRepo.GetByIdAsync(complainant.PersonId);
                    if (person != null)
                    {
                        complainant.Person = person;
                    }
                }
            }
            
            foreach (var respondent in entity.Respondents)
            {
                if (respondent.Person == null || string.IsNullOrEmpty(respondent.Person.FirstName))
                {
                    var person = await _personRepo.GetByIdAsync(respondent.PersonId);
                    if (person != null)
                    {
                        respondent.Person = person;
                    }
                }
            }
            
            return _mapper.Map<ComplaintFormObject>(entity);
        }

        public async Task<ObjectId> CreateOrUpdateAsync(ComplaintFormObject dto)
        {
            if (dto.Id == ObjectId.Empty)
            {
                // Create new complaint
                var entity = _mapper.Map<Complaint>(dto);
                await _repo.AddAsync(entity);
                await ((Project.Gawad.Infrastructure.Repositories.ComplaintRepository)_repo).SaveChangesAsync();
                
                var persistedId = entity.Id.GetValueOrDefault();
                dto.Id = persistedId;
                return persistedId;
            }
            else
            {
                // Update existing complaint
                var existingEntity = await _repo.GetByIdAsync(dto.Id);
                if (existingEntity == null)
                    throw new InvalidOperationException("Complaint not found");

                // Update the existing entity with new values
                Console.WriteLine($"Before update - Subject: {existingEntity.Subject}, New Subject: {dto.Subject}");
                existingEntity.Subject = dto.Subject;
                existingEntity.Details = dto.Details;
                existingEntity.ComplaintType = dto.ComplaintType;
                existingEntity.Status = dto.Status;
                existingEntity.IncidentDateTime = dto.IncidentDateTime;
                existingEntity.ReportedDate = dto.ReportedDate;
                existingEntity.DateReceived = dto.DateReceived;
                existingEntity.BlotterReceivedBy = dto.BlotterReceivedBy;
                existingEntity.ORNumber = dto.ORNumber;
                existingEntity.TotalFee = dto.TotalFee;
                existingEntity.Note = dto.Note;
                existingEntity.Recommendation = dto.Recommendation;
                existingEntity.LastModifiedDate = DateTime.UtcNow;
                Console.WriteLine($"After update - Subject: {existingEntity.Subject}");

                // Update complainants
                existingEntity.Complainants.Clear();
                foreach (var complainantDto in dto.Complainants)
                {
                    var complainant = new Complainant
                    {
                        Id = ObjectId.GenerateNewId(),
                        PersonId = complainantDto.PersonId,
                        Person = new Person
                        {
                            Id = ObjectId.GenerateNewId(),
                            FirstName = complainantDto.FirstName,
                            LastName = complainantDto.LastName,
                            MiddleName = complainantDto.MiddleName,
                            Suffix = complainantDto.Suffix,
                            DateOfBirth = complainantDto.DateOfBirth ?? default,
                            PlaceOfBirth = complainantDto.PlaceOfBirth,
                            Gender = complainantDto.Gender,
                            CivilStatus = complainantDto.CivilStatus,
                            SpouseName = complainantDto.SpouseName,
                            FatherName = complainantDto.FatherName,
                            MotherMaidenName = complainantDto.MotherMaidenName
                        }
                    };
                    existingEntity.Complainants.Add(complainant);
                }

                // Update respondents
                existingEntity.Respondents.Clear();
                foreach (var respondentDto in dto.Respondents)
                {
                    var respondent = new Respondent
                    {
                        Id = ObjectId.GenerateNewId(),
                        PersonId = respondentDto.PersonId,
                        Person = new Person
                        {
                            Id = ObjectId.GenerateNewId(),
                            FirstName = respondentDto.FirstName,
                            LastName = respondentDto.LastName,
                            MiddleName = respondentDto.MiddleName
                        },
                        Age = respondentDto.Age,
                        Gender = respondentDto.Gender,
                        CivilStatus = respondentDto.CivilStatus,
                        Occupation = respondentDto.Occupation,
                        Address = new Address
                        {
                            AddressLine1 = respondentDto.Address
                        }
                    };
                    existingEntity.Respondents.Add(respondent);
                }

                await _repo.UpdateAsync(existingEntity);
                await ((Project.Gawad.Infrastructure.Repositories.ComplaintRepository)_repo).SaveChangesAsync();

                return dto.Id;
            }
        }

        public async Task ChangeComplaintStatusAsync(string id, ComplaintStatus newStatus)
        {
            try
            {
                if (!ObjectId.TryParse(id, out var oid))
                    throw new ArgumentException("Invalid complaint ID", nameof(id));

                Console.WriteLine($"Before update - ID: {id}, New Status: {newStatus}");
                
                // Use the repository's UpdateStatusAsync method to avoid tracking issues
                await ((Project.Gawad.Infrastructure.Repositories.ComplaintRepository)_repo).UpdateStatusAsync(oid, newStatus);
                Console.WriteLine($"Status update completed successfully");
                
                // Verify the update by fetching the entity again
                var verificationEntity = await _repo.GetByIdAsync(oid);
                Console.WriteLine($"After verification - Status: {verificationEntity?.Status}");
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Error in ChangeComplaintStatusAsync: {ex.Message}");
                Console.WriteLine($"Stack trace: {ex.StackTrace}");
                throw;
            }
        }

        public async Task DeleteAsync(string id)
        {
            if (string.IsNullOrWhiteSpace(id))
                throw new ArgumentException("Invalid complaint ID", nameof(id));

            if (!ObjectId.TryParse(id, out var oid))
                throw new ArgumentException("Invalid complaint ID", nameof(id));

            var entity = await _repo.GetByIdAsync(oid)
                         ?? throw new InvalidOperationException("Complaint not found");

            // Clear the navigation properties to avoid tracking issues
            entity.Complainants.Clear();
            entity.Respondents.Clear();
            
            entity.IsDeleted = true;
            entity.LastModifiedDate = DateTime.UtcNow;
            
            await _repo.UpdateAsync(entity);
            await ((Project.Gawad.Infrastructure.Repositories.ComplaintRepository)_repo).SaveChangesAsync();
        }
    }
}