$(document).ready(function () {

    $('[data-toggle="tooltip"]').tooltip();

    let dt = new DataTable('#tblMain', {
        "lengthMenu": [[10, 25, 50, 100, 500, 1000], [10, 25, 50, 100, 500, 1000]],
        "info": true,
        // "layout": {
        //     topStart: {
        //         buttons: ['csv', 'excel', 'pdf', 'print']
        //     }
        // },
        "buttons": ['csv', 'excel', 'pdf', 'print'],
        "dom": "<'dt-layout-top dt-start'lfB>rtip",
        "infoCallback": function (settings, start, end, max, total, pre) {
            settings.api = this.api();
        },
        "processing": true,
        "serverSide": true,
        "ajax": function (data, callback, settings) {

            let url = '/Transactions/GetTransactionsList';

            const params = $.param({
                search: data.search.value,
                page: settings.api.page.info().page + 1,
                itemsPerPage: settings.api.page.len(),
                sortColIndex: data.order[0].column,
                sortColDir: data.order[0].dir
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
                "data": "id",
                "className": "dt-center row-btn-view",
                "render": (id) => `<a href="/Transactions/TransactionDetails/${id}" data-toggle="tooltip" title="View Transaction Details"><i class="bi bi-info-circle-fill"/></a>`,
                "orderable": false
            },
            {
                "data": "id",
                "className": "dt-center row-btn-view",
                "render": (id) => `<a href="#" class="removeTransaction" data-toggle="tooltip" title="Remove Trasaction Record"  data-id="${id}"><i class="bi bi-trash3-fill" data-id="${id}" /></a>`,
                "orderable": false
            },
            {
                "data": "controlNumber",
                "title": "Control Number",
                "defaultContent": ''
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

    $(document).on('click', '.removeTransaction', function (e) {
        e.preventDefault();

        let transactionId = $(this).parent().next('td').text();
        let c = confirm('Are you sure you want to remove transaction ' + transactionId + ' record?');

        if (!c) return;

        let id = $(this).attr('data-id');

        $.ajax({
            url: "/Transactions/DeleteTransaction/" + id,
            type: "DELETE",
            success: function () {
                //alert("DELETE Request Success: Item removed.");
                dt.ajax.reload();
            },
            error: function (xhr, status, error) {
                console.log("Error: " + error);
            }
        });

        console.log('clicked deleteResident: ' + id);
    });

});