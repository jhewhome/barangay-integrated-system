$(function () {
    $('#DateOfBirth').daterangepicker({
        locale: {
            format: 'MM/DD/YYYY',
        },
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        maxDate: new Date(new Date().setDate(new Date().getDate()))
    });


    $('.select2').select2()

    //Initialize Select2 Elements
    $('.select2bs4').select2({
        theme: 'bootstrap4'
    })
});