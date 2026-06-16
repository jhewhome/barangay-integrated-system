$(document).ready(function () {
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

                let url = '/UserManagement/GetUsersList';

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
                    "className": "dt-center row-btn-update",
                    "render": (id) => `<a href="/UserManagement/UpdateUser/${id}" data-toggle="tooltip" title="View Resident Profile"><i class="bi bi-pencil-fill"/></a>`,
                    "orderable": false
                },
                {
                    "data": "id",
                    "className": "dt-center row-btn-lock",
                    "render": (id) => `<a href="/UserManagement/Lock/${id}" data-toggle="tooltip" title="View Resident Profile"><i class="bi bi-lock-fill"/></a>`,
                    "orderable": false
                },
                {
                    "data": "id",
                    "className": "dt-center row-btn-view",
                    "render": (id) => `<a href="/UserManagement/ResetPassword/${id}" data-toggle="tooltip" title="Reset Password"><i class="bi bi-arrow-counterclockwise"/></a>`,
                    "orderable": false
                },
                {
                    "data": "userName",
                    "title": "User Name"
                },
                {
                    "data": "fullName",
                    "title": "Full Name"
                },
                {
                    "data": "createdDateTime",
                    "title": "Date Created",
                    "defaultContent": "",
                    "render": DataTable.render.datetime('Do MMM YYYY')
                },

                {
                    "data": "lastModifiedDate",
                    "title": "Last Modified Date",
                    "defaultContent": '',
                    "render": DataTable.render.datetime('Do MMM YYYY')
                },
            ]
        })
    ;
});