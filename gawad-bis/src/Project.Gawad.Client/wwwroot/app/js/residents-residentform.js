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
    
    // Format dates before form submission
    $('form').on('submit', function(e) {
        var dateOfBirth = $('#DateOfBirth').val();
        
        if (dateOfBirth) {
            try {
                // Try parsing with MM/DD/YYYY format first
                var dateOfBirthObj = moment(dateOfBirth, 'MM/DD/YYYY', true); // strict mode
                if (!dateOfBirthObj.isValid()) {
                    // Fallback: try parsing as-is (might already be in YYYY-MM-DD format)
                    dateOfBirthObj = moment(dateOfBirth);
                }
                if (dateOfBirthObj.isValid()) {
                    $('#DateOfBirth').val(dateOfBirthObj.format('YYYY-MM-DD'));
                    console.log('Formatted DateOfBirth:', dateOfBirthObj.format('YYYY-MM-DD'));
                } else {
                    console.error('Invalid DateOfBirth format:', dateOfBirth);
                    e.preventDefault();
                    alert('Please enter a valid date of birth.');
                    $('#DateOfBirth').focus();
                    return false;
                }
            } catch (err) {
                console.error('Error formatting date of birth:', err);
                e.preventDefault();
                alert('Please enter a valid date of birth.');
                $('#DateOfBirth').focus();
                return false;
            }
        }
        
        console.log('Form validation passed, submitting...');
        return true;
    });
});