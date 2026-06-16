$(document).ready(function () {

    $('[data-toggle="tooltip"]').tooltip();

    let dt = new DataTable('#tblMain', {
        "lengthMenu": [[10, 25, 50, 100, 500, 1000], [10, 25, 50, 100, 500, 1000]],
        "info": true,
        // "layout": {
        //     top: {
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

            let url = '/Residents/GetResidentsList';

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
                "render": (id) => `<a href="/Residents/Profile/${id}" data-toggle="tooltip" title="View Resident Profile"><i class="bi bi-file-person-fill"/></a>`,
                "orderable": false
            },
            {
                "data": "id",
                "className": "dt-center row-btn-view",
                "render": (id) => `<a href="#" class="removeResident" data-toggle="tooltip" title="Remove Resident Record" data-id="${id}"><i class="bi bi-trash3-fill" /></a>`,
                "orderable": false
            },
            {
                "data": "id",
                "className": "dt-center row-btn-update",
                "render": (id) => `<a href="/Residents/UpdateResident/${id}" data-toggle="tooltip" title="Update Resident Profile"><i class="bi bi-pencil-square"/></a>`,
                "orderable": false
            },
            {
                "data": "name",
                "title": "Full Name",
                "defaultContent": ''
            },
            {
                "data": "gender",
                "title": "Gender",
                "defaultContent": ''
            },
            {
                "data": "civilStatus",
                "title": "Civil Status",
                "defaultContent": ''
            },
            {
                "data": "address",
                "title": "Address",
                "defaultContent": ''
            },
        ]
    });

    $(document).on('click', '.removeResident', function (e) {
        e.preventDefault();

        let c = confirm('Are you sure you want to remove resident record?');

        if (!c) return;

        let id = $(this).attr('data-id');

        $.ajax({
            url: "/Residents/DeleteResident/" + id,
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