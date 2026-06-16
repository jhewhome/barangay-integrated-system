$(function () {
    let dtComplaints = new DataTable('#tblComplaints', {
        "language": {
            "emptyTable": 'No complaint records found',
            "zeroRecords": 'No complaint records found',
        },
        "paging": false,
        "searching": false,
        "info": false
    });

    let dtTransctionss = new DataTable('#tblTransactions', {
        "infoCallback": function (settings, start, end, max, total, pre) {
            settings.api = this.api();
        },
        "processing": true,
        "serverSide": true,
        "ajax": function (data, callback, settings) {

            let url = '/Transactions/GetResidentTransactionsList';

            let residentId = $("#tblTransactions").attr('data-id');

            const params = $.param({
                search: data.search.value,
                page: settings.api.page.info().page + 1,
                itemsPerPage: settings.api.page.len(),
                sortColIndex: data.order[0].column,
                sortColDir: data.order[0].dir,
                residentId: residentId,
            });

            $.ajax({
                url: `${url}?${params}`,
                method: 'GET',
                data: null,
                success: function (response) {
                    callback(response);
                }
            });
        },
        "columns": [
            {
                "data": "controlNumber",
                "title": "Control Number",
                "render": function (data, type, row) {
                    if (type === 'display') {
                        return `<a href="/Transactions/TransactionDetails/${row['id']}" target="_blank" rel="noopener noreferrer" data-toggle="tooltip" title="View Transaction Details">${row['controlNumber']}</a>`;
                    }

                    return data;
                },
                //"defaultContent": ''
            },
            {
                "data": "requesterName",
                "title": "Requester Name",
                "defaultContent": ''
            },
            {
                "data": "transactionType",
                "title": "Transaction Type",
                "defaultContent": ''
            },
            {
                "data": "paidAmount",
                "title": "Paid Amount",
                "defaultContent": ''
            },
            {
                "data": "createdOn",
                "title": "Date and Time",
                "defaultContent": ''
            },
        ]
    });
});