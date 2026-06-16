using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.Sales;

namespace Project.Gawad.Core.Services;

public interface ISalesService
{
    Task<ServiceResponse<CreateSaleObject>> CreateSale(CreateSaleObject createSaleObject, ApplicationUserObject createdBy);

    Task<ServiceResponse<CreatePaymentObject>> ProcessPayment(CreatePaymentObject createPaymentObject, ApplicationUserObject createdBy);

    Task<bool> CancelSale(string saleId, ApplicationUserObject cancelledBy);
}



