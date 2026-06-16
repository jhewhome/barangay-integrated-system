using Project.Gawad.Domain.Enums.Complaints;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace Project.Gawad.Domain.Objects.Complaint
{
    public class ComplaintListObject
    {
        public string Id { get; set; }
        public string ComplainantName { get; set; }
        public ComplaintType Type { get; set; }
        public ComplaintStatus Status { get; set; }
        public DateTime IncidentDate { get; set; }
    }
}
