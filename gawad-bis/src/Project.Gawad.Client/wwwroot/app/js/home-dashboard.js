$(function () {
    // Suppress DataTables error messages globally
    $.fn.dataTable.ext.errMode = 'none';
    
    let dtBirthdays = new DataTable('#tblBirthdays', {
        "language": {
            "emptyTable": 'No birthdays today',
            "zeroRecords": 'No birthdays today',
        },
        "paging": false,
        "scrollY": '400px', // Set the height of the scrollable area
        "scrollCollapse": true,
        "searching": false,
        "info": false,
        "processing": true,
        "ajax": {
            "url": "/Home/GetTodayBirthdays",
            "type": "GET",
            "dataSrc": "data"
        },
        "columns": [
            {
                "data": "fullName",
                "title": "Name",
                "defaultContent": ''
            },
            {
                "data": "age",
                "title": "Age",
                "defaultContent": ''
            },
            {
                "data": "dateOfBirth",
                "title": "Birthday",
                "defaultContent": '',
                "render": function(data, type, row) {
                    if (data) {
                        return new Date(data).toLocaleDateString();
                    }
                    return '';
                }
            }
        ]
    });

    // Handle DataTable errors silently for birthdays
    dtBirthdays.on('error.dt', function (e, settings, techNote, message) {
        console.error('Birthdays DataTable error:', message);
        // Don't show error to user, just log it
    });

    let dtTransactions = new DataTable('#tblTransactions', {
        "language": {
            "emptyTable": 'No Transactions for the last 7 days',
            "zeroRecords": 'No Transactions for the last 7 days',
        },
        "infoCallback": function (settings, start, end, max, total, pre) {
            settings.api = this.api();
        },
        "searching": false,
        "lengthChange": false,
        "processing": true,
        "serverSide": true,
        "ajax": function (data, callback, settings) {

            let url = '/Transactions/GetRecentTransactionsList';

            let lastndays = $("#tblTransactions").attr('data-lastndays');

            const params = $.param({
                search: data.search.value,
                page: settings.api.page.info().page + 1,
                itemsPerPage: settings.api.page.len(),
                sortColIndex: data.order[0].column,
                sortColDir: data.order[0].dir,
                lastNDays: lastndays,
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

    // Handle DataTable errors silently for transactions
    dtTransactions.on('error.dt', function (e, settings, techNote, message) {
        console.error('Transactions DataTable error:', message);
        // Don't show error to user, just log it
    });

});