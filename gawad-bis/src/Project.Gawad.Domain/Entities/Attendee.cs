using MongoDB.Bson;
using System.ComponentModel.DataAnnotations.Schema;

namespace Project.Gawad.Domain.Entities;

public class Attendee : Entity
{
    [ForeignKey(nameof(EventId))] 
    public ObjectId EventId { get; set; }
    
    [ForeignKey(nameof(PersonId))] 
    public ObjectId PersonId { get; set; }
    
    public Person Person { get; set; } = new Person();
    
    public DateTime RegisteredDate { get; set; } = DateTime.UtcNow;
    
    public bool IsPresent { get; set; } = false;
    
    public DateTime? CheckInTime { get; set; }
    
    public DateTime? CheckOutTime { get; set; }
    
    public string? Notes { get; set; }
}
