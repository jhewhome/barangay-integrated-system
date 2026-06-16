using System.ComponentModel.DataAnnotations;
using MongoDB.Bson;
using Project.Gawad.Domain.Enums.Medicine;

namespace Project.Gawad.Domain.Objects.Medicine;

public class UpdateMedicineObject : CreateMedicineObject
{
    [Required]
    public ObjectId MedicineId { get; set; }
}




