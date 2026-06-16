$(document).ready(function () {

    $('[data-toggle="tooltip"]').tooltip();

    let dt = new DataTable('#tblMain', {
        "layout": {
            topStart: {
                buttons: ['csv', 'excel', 'pdf', 'print']
            }
        },
        "infoCallback": function (settings, start, end, max, total, pre) {
            settings.api = this.api();
        },
        "processing": true,
        "serverSide": true,
        "ajax": function (data, callback, settings) {

            let url = '/VisitorsLog/GetVisitorsList';

            console.log(settings.api.page.len());

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
                "data": "fullName",
                "title": "Name",
                "defaultContent": '',
                "orderable": false
            },
            {
                "data": "purpose",
                "title": "Purpose of Visit",
                "defaultContent": '',
                "orderable": false
            },
            {
                "data": "registereDateTime",
                "title": "Date and Time",
                "defaultContent": '',
                "render": function(data, type, row) {
                    if (data) {
                        return new Date(data).toLocaleString();
                    }
                    return '';
                }
            },
        ]
    });

});