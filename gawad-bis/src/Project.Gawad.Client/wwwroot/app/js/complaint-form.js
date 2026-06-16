$(function () {
    // Initialize Select2 dropdowns
    $('.select2bs4').select2({ theme: 'bootstrap4' });

    // When arrow icon in resident finder is clicked (for Complaints module)
    $(document).on('click', '.tbl-person-finder a[href*="/Complaints/GetResidentDetails"]', function (e) {
        e.preventDefault();
        const url = $(this).attr('href');

        $.get(url, function (data) {
            $('#complainantFullName').val(data.fullName);
            $('#complainantContactNumber').val(data.contactNumber);
            $('#complainantAddress').val(data.address);
            $('#complainantPersonId').val(data.personId);
            $('.person-finder').modal('hide');
        })
            .fail(() => alert("Error fetching resident info."));
    });
});
