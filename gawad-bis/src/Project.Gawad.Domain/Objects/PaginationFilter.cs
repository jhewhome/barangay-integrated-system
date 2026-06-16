namespace Project.Gawad.Domain.Objects;

public class PaginationFilter
{
    public int StartIndex { get; set; }

    public int RowPerPage { get; set; }

    public string SearchKey { get; set; }
}