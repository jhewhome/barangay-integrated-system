$(document).ready(function () {
    console.log('Complaints index script loaded');

    $('[data-toggle="tooltip"]').tooltip();

    // Suppress DataTables error messages globally
    $.fn.dataTable.ext.errMode = 'none';

    console.log('Initializing DataTable for #tblMain');
    console.log('Table element exists:', $('#tblMain').length > 0);
    console.log('Table element:', $('#tblMain')[0]);
    
    let dt = new DataTable('#tblMain', {
        "lengthMenu": [[10, 25, 50, 100, 500, 1000], [10, 25, 50, 100, 500, 1000]],
        "info": true,
        "buttons": ['csv', 'excel', 'pdf', 'print'],
        "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
               "<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        "infoCallback": function (settings, start, end, max, total, pre) {
            settings.api = this.api();
        },
        "processing": true,
        "serverSide": true,
        "language": {
            "processing": "Loading...",
            "emptyTable": "No complaints found",
            "zeroRecords": "No matching complaints found"
        },
        "ajax": function (data, callback, settings) {

            let url = '/Complaints/GetComplaintsList';

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
                    console.log('DataTables response:', response);
                    callback(response);
                },
                error: function (xhr, status, error) {
                    console.error('DataTables AJAX error:', error);
                    // Return empty data instead of showing error
                    callback({
                        data: [],
                        recordsTotal: 0,
                        recordsFiltered: 0
                    });
                }
            });
        },
        "columns": [
            {
                "data": "id",
                "className": "dt-center row-btn-view",
                "render": (id) => `<button type="button" class="view-complaint" data-id="${id}" data-toggle="tooltip" title="View Details"><i class="bi bi-file-person-fill"/></button>`,
                "orderable": false
            },
            {
                "data": "id",
                "className": "dt-center row-btn-update",
                "render": (id) => `<a href="/Complaints/UpdateComplaint/${id}" data-toggle="tooltip" title="Edit"><i class="bi bi-pencil-square"/></a>`,
                "orderable": false
            },
            {
                "data": "id",
                "className": "dt-center row-btn-view",
                "render": (id) => `<a href="/Complaints/EditStatus/${id}" data-toggle="tooltip" title="Change Status"><i class="bi bi-gear-fill"/></a>`,
                "orderable": false
            },
            {
                "data": "id",
                "className": "dt-center row-btn-view",
                "render": (id) => `<button type="button" class="delete-complaint" data-id="${id}" data-toggle="tooltip" title="Delete"><i class="bi bi-trash3-fill"/></button>`,
                "orderable": false
            },
            {
                "data": "id",
                "title": "ID",
                "defaultContent": ''
            },
            {
                "data": "complainantName",
                "title": "Complainant",
                "defaultContent": ''
            },
            {
                "data": "type",
                "title": "Type",
                "defaultContent": ''
            },
            {
                "data": "status",
                "title": "Status",
                "defaultContent": ''
            },
            {
                "data": "incidentDate",
                "title": "Incident Date",
                "defaultContent": ''
            }
        ]
    });

    // Handle DataTable errors silently
    dt.on('error.dt', function (e, settings, techNote, message) {
        console.error('DataTable error:', message);
        // Don't show error to user, just log it
    });

    // Test if DataTable initialized properly
    dt.on('init.dt', function() {
        console.log('DataTable initialized successfully');
    });

    // Handle view complaint button click
    $(document).on('click', '.view-complaint', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        
        // Show loading state
        $('#complaintDetailsModal .modal-body').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        $('#complaintDetailsModal').modal('show');
        
        // Fetch complaint details
        fetch(`/Complaints/GetComplaintDetails?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateModal(data.data);
                } else {
                    $('#complaintDetailsModal .modal-body').html(`<div class="alert alert-danger">Error: ${data.message}</div>`);
                }
            })
            .catch(error => {
                $('#complaintDetailsModal .modal-body').html(`<div class="alert alert-danger">Error loading complaint details: ${error.message}</div>`);
            });
    });

    // Handle delete complaint button click
    $(document).on('click', '.delete-complaint', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        const confirmDelete = confirm('Are you sure you want to delete this complaint? This action cannot be undone.');
        if (!confirmDelete) return;

        const token = $('input[name="__RequestVerificationToken"]').val();
        if (!token) {
            alert('Anti-forgery token not found. Please refresh the page and try again.');
            return;
        }

        const formData = new FormData();
        formData.append('__RequestVerificationToken', token);

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000);

        fetch(`/Complaints/Delete?id=${id}`, {
            method: 'POST',
            body: formData,
            signal: controller.signal
        })
            .then(res => { clearTimeout(timeoutId); return res.json(); })
            .then(data => {
                if (data.success) {
                    alert('Complaint deleted successfully');
                    dt.ajax.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                clearTimeout(timeoutId);
                if (err.name === 'AbortError') {
                    alert('Delete request timed out. Please try again.');
                } else {
                    alert('An error occurred: ' + err.message);
                }
            });
    });

    // Function to populate the modal with complaint data
    function populateModal(complaint) {
        $('#modalComplaintId').text(complaint.id || '');
        $('#modalComplaintType').text(complaint.complaintType || '');
        $('#modalStatus').text(complaint.status || '');
        $('#modalDetails').text(complaint.details || '');
        $('#modalDateReceived').text(complaint.dateReceived ? new Date(complaint.dateReceived).toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }) : '');
        $('#modalBlotterReceivedBy').text(complaint.blotterReceivedBy || '');
        $('#modalORNumber').text(complaint.orNumber || '');
        $('#modalTotalFee').text(complaint.totalFee || '');
        $('#modalNote').text(complaint.note || '');
        $('#modalRecommendation').text(complaint.recommendation || '');
        
        // Update edit button href
        $('#modalEditButton').attr('href', `/Complaints/UpdateComplaint/${complaint.id}`);
        
        // Populate complainants
        if (complaint.complainants && complaint.complainants.length > 0) {
            let complainantsHtml = '';
            complaint.complainants.forEach(complainant => {
                complainantsHtml += `
                    <tr>
                        <td>${complainant.firstName || ''} ${complainant.lastName || ''}</td>
                        <td>${complainant.contactNumber || ''}</td>
                        <td>${complainant.dateOfBirth ? new Date(complainant.dateOfBirth).toLocaleDateString('en-US', { 
                            year: 'numeric', 
                            month: 'short', 
                            day: '2-digit' 
                        }) : ''}</td>
                    </tr>
                `;
            });
            $('#modalComplainants').html(complainantsHtml);
        } else {
            $('#modalComplainants').html('<tr><td colspan="3" class="text-center text-muted">No complainants found</td></tr>');
        }
        
        // Populate respondents
        if (complaint.respondents && complaint.respondents.length > 0) {
            let respondentsHtml = '';
            complaint.respondents.forEach(respondent => {
                respondentsHtml += `
                    <tr>
                        <td>${respondent.firstName || ''} ${respondent.lastName || ''}</td>
                        <td>${respondent.address || ''}</td>
                        <td>${respondent.age || ''}</td>
                        <td>${respondent.occupation || ''}</td>
                    </tr>
                `;
            });
            $('#modalRespondents').html(respondentsHtml);
        } else {
            $('#modalRespondents').html('<tr><td colspan="4" class="text-center text-muted">No respondents found</td></tr>');
        }
    }

});