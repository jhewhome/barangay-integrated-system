namespace Project.Gawad.Core.Helpers;

public static class PaginationHelper
{
    public static int CalculatePages(int totalItems, int totalItemsPerPage)
    {
        var totalPage = 0;

        if (totalItems % totalItemsPerPage == 0)
            totalPage = totalItems / totalItemsPerPage;
        else if (totalItems < totalItemsPerPage)
            totalPage = 1;
        else
            totalPage = totalItems / totalItemsPerPage + 1;

        return totalPage;
    }
}