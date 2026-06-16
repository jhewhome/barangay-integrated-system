using Microsoft.AspNetCore.Mvc;
using MongoDB.Bson;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Services;

namespace Project.Gawad.Client.Controllers;

public class TransactionsController(
    IBarangayTransactionProvider barangayTransactionProvider,
    IBarangayTransactionService barangayTransactionService,
    IDocumentService documentService,
    IUsersProvider usersProvider,
    ILogger<TransactionsController> logger) : Controller
{
    private readonly IBarangayTransactionProvider _barangayTransactionProvider =
        barangayTransactionProvider ?? throw new ArgumentNullException(nameof(barangayTransactionProvider));

    private readonly IBarangayTransactionService _barangayTransactionService =
        barangayTransactionService ?? throw new ArgumentNullException(nameof(barangayTransactionService));

    private readonly ILogger<TransactionsController> _logger =
        logger ?? throw new ArgumentNullException(nameof(logger));

    private readonly IDocumentService _documentService =
        documentService ?? throw new ArgumentNullException(nameof(documentService));

    private readonly IUsersProvider _usersProvider =
        usersProvider ?? throw new ArgumentNullException(nameof(usersProvider));


    [HttpGet]
    public IActionResult Index()
    {
        return View();
    }

    [HttpGet]
    public async Task<IActionResult> GetTransactionsList(int page = 1, int itemsPerPage = 0, int sortColIndex = 0,
        string sortColDir = "asc", string? search = null)
    {
        var paginatedData =
            await _barangayTransactionProvider.GetTransactionsListAsync(page, itemsPerPage, sortColIndex, sortColDir,
                search);
        return Ok(paginatedData);
    }

    [HttpGet]
    public async Task<IActionResult> GetResidentTransactionsList(string residentId, int page = 1, int itemsPerPage = 0,
        int sortColIndex = 0,
        string sortColDir = "asc", string? search = null)
    {
        var paginatedData =
            await _barangayTransactionProvider.GetResidentTransactionsListAsync(residentId, page, itemsPerPage,
                sortColIndex, sortColDir,
                search);
        return Ok(paginatedData);
    }

    [HttpGet]
    public async Task<IActionResult> TransactionDetails(string id)
    {
        if (string.IsNullOrWhiteSpace(id))
            return RedirectToAction(nameof(Index));

        var transactionDetails = await _barangayTransactionProvider.GetTransactionDetailObjectAsync(ObjectId.Parse(id));

        if (transactionDetails is null)
            return RedirectToAction(nameof(Index));

        return View(transactionDetails);
    }

    [HttpGet]
    public async Task<IActionResult> TransactionDocument(string id)
    {
        if (string.IsNullOrWhiteSpace(id))
            return RedirectToAction(nameof(Index));

        _logger.LogInformation("Requested document for Transaction ID: {TransactionId}", id);

        try
        {
            var documentData = await _barangayTransactionProvider.GetTransactionDocumentDetailsAsync(ObjectId.Parse(id));
            var documentType = await _barangayTransactionProvider.GetDocumetTypeAsync(ObjectId.Parse(id));

            var bytes = await _documentService.GenerateDocument(id, documentType, documentData);

            if (bytes == null || bytes.Length == 0)
            {
                _logger.LogWarning("Document generation returned no content for Transaction ID: {TransactionId}", id);
                return NotFound();
            }

            // Return inline PDF so browsers can preview it in an <iframe> or tab
            return File(bytes, "application/pdf");
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error generating document for Transaction ID: {TransactionId}", id);
            return StatusCode(500, "Failed to generate document.");
        }
    }

    [HttpGet]
    public async Task<IActionResult> GetRecentTransactionsList(int lastNDays, int page = 1, int itemsPerPage = 0,
        int sortColIndex = 0,
        string sortColDir = "asc", string? search = null)
    {
        var paginatedData =
            await _barangayTransactionProvider.GetRecentTransactionsListAsync(lastNDays, page, itemsPerPage,
                sortColIndex, sortColDir,
                search);
        return Ok(paginatedData);
    }

    [HttpDelete]
    public async Task<IActionResult> DeleteTransaction(string id)
    {
        if (string.IsNullOrWhiteSpace(id))
            return BadRequest();

        try
        {
            var deletedBy = await _usersProvider.GetCurrentUserAsync(HttpContext.User);

            await _barangayTransactionService.DeleteTransactionAsync(id, deletedBy);
        }
        catch (Exception e)
        {
            _logger.LogError(e, e.Message);
            return StatusCode(500, e.Message);
        }

        return Ok();
    }
}