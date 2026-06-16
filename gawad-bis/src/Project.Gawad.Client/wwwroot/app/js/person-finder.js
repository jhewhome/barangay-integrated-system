$(function () {
    $('.person-finder').on('shown.bs.modal', function () {
        console.log('Person finder shown');

        let url = $('.btn-person-finder').attr('data-url');

        $('input[type=search]').focus();

        let dt = new DataTable('.tbl-person-finder', {
            "lengthMenu": [[5, 10, 15, 20, 25], [5, 10, 15, 20, 25]],
            "info": true,
            "infoCallback": function (settings, start, end, max, total, pre) {
                settings.api = this.api();
            },
            "processing": true,
            "serverSide": true,
            "retrieve": true,
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
                    "data": "name",
                    "title": "Full Name",
                    "defaultContent": ''
                },
                {
                    "data": "address",
                    "title": "Address",
                    "defaultContent": ''
                },
                {
                    "data": "gender",
                    "title": "Gender",
                    "defaultContent": ''
                },
                {
                    "data": "id",
                    "className": "dt-center row-btn-view",
                    "render": (id) => `<a href="${url}${id}" data-toggle="tooltip" title="Load"><i class="bi bi-arrow-right-circle-fill"/></a>`,
                    "orderable": false
                }
            ]
        });

    });
});