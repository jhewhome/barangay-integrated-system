namespace Project.Gawad.Domain.Objects;

public class PaginatedRecords<T>
{
    public PaginatedRecords()
    {
    }

    public PaginatedRecords(int pageNumber, int pageSize, int recordsTotal, int recordsFiltered, ICollection<T> data)
    {
        PageNumber = pageNumber;
        PageSize = pageSize;
        RecordsTotal = recordsTotal;
        RecordsFiltered = recordsFiltered;
        Data = data;
    }

    public int PageNumber { get; set; }

    public int PageSize { get; set; }

    public int RecordsTotal { get; set; }

    public int RecordsFiltered { get; set; }

    public ICollection<T> Data { get; set; }
}