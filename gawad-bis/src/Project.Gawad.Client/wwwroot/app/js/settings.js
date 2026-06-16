$(document).ready(function () {
    let signaturePad = null;
    let existingSignaturePath = window.existingSignaturePath || null;

    // Display existing signature if available
    if (existingSignaturePath && existingSignaturePath.length > 0) {
        $('#officialSignaturePreviewImg').attr('src', '/' + existingSignaturePath);
        $('#officialSignaturePreview').show();
    }

    // Initialize Signature Pad for official signature
    const canvas = document.getElementById('officialSignaturePad');
    if (canvas) {
        signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 0)',
            minWidth: 1,
            maxWidth: 3,
            throttle: 16
        });

        // Load existing signature onto canvas if available
        if (existingSignaturePath) {
            const img = new Image();
            img.onload = function () {
                const ctx = canvas.getContext('2d');
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * ratio;
                canvas.height = rect.height * ratio;
                ctx.scale(ratio, ratio);
                
                ctx.drawImage(img, 0, 0, rect.width, rect.height);
                signaturePad.fromDataURL(canvas.toDataURL());
            };
            img.src = '/' + existingSignaturePath;
        }

        // Adjust canvas size on resize
        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width * ratio;
            canvas.height = rect.height * ratio;
            canvas.getContext('2d').scale(ratio, ratio);
            
            // Reload signature pad data after resize if it has content
            if (!signaturePad.isEmpty()) {
                const dataURL = signaturePad.toDataURL();
                signaturePad.clear();
                signaturePad.fromDataURL(dataURL);
            }
        }

        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();
    }

    // Clear signature pad
    $('#btnClearOfficialSignature').on('click', function () {
        if (signaturePad) {
            signaturePad.clear();
            $('#officialSignaturePreview').hide();
            $('#officialSignaturePreviewImg').attr('src', '');
            $('#signaturePath').val('');
            $('#signatureFile').val('');
            clearValidationError('officialSignature');
        }
    });

    // Toggle upload section
    let uploadSectionVisible = false;
    $('#btnToggleUploadOfficialSignature').on('click', function () {
        uploadSectionVisible = !uploadSectionVisible;
        if (uploadSectionVisible) {
            $('#officialSignatureUploadSection').slideDown();
            $(this).html('<i class="bi bi-hide"></i> Hide Upload Button');
        } else {
            $('#officialSignatureUploadSection').slideUp();
            $(this).html('<i class="bi bi-upload"></i> Upload from Computer');
        }
    });

    // Handle file upload
    $('#signatureFile').on('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            showValidationError('officialSignature', 'Signature image file size must be less than 5MB.');
            $(this).val('');
            return;
        }

        // Validate file type
        if (!file.type.match('image.*')) {
            showValidationError('officialSignature', 'Please upload a valid image file (PNG, JPG, JPEG).');
            $(this).val('');
            return;
        }

        clearValidationError('officialSignature');

        // Display preview
        const reader = new FileReader();
        reader.onload = function (e) {
            $('#officialSignaturePreviewImg').attr('src', e.target.result);
            $('#officialSignaturePreview').show();

            // Also draw on signature pad
            if (signaturePad) {
                const img = new Image();
                img.onload = function () {
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    signaturePad.clear();
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                };
                img.src = e.target.result;
            }
        };
        reader.readAsDataURL(file);
    });

    // Validation functions
    function showValidationError(fieldId, message) {
        $('#' + fieldId + '-validation-error').text(message).show();
        $('#' + fieldId + 'Pad, #' + fieldId + 'File').closest('.signature-pad-container, .mb-3').addClass('is-invalid');
    }

    function clearValidationError(fieldId) {
        $('#' + fieldId + '-validation-error').text('').hide();
        $('#' + fieldId + 'Pad, #' + fieldId + 'File').closest('.signature-pad-container, .mb-3').removeClass('is-invalid');
    }

    // Clear validation errors on field changes
    $('#officialSignaturePad, #signatureFile').on('input change', function () {
        if (signaturePad && !signaturePad.isEmpty()) {
            clearValidationError('officialSignature');
        }
        if ($('#signatureFile')[0].files.length > 0) {
            clearValidationError('officialSignature');
        }
    });

    // Form submission handler with validation
    $('#settingsForm').on('submit', function (e) {
        console.log('Settings form submission started');

        // Clear all previous validation errors
        $('.is-invalid').removeClass('is-invalid');
        $('.field-validation-error').text('').hide();
        $('[id$="-validation-error"]').text('').hide();

        var hasErrors = false;
        var firstErrorField = null;

        // Validate signatory name
        var signatoryName = $('#SignatoryName').val();
        if (!signatoryName || signatoryName.trim() === '') {
            e.preventDefault();
            $('#SignatoryName').addClass('is-invalid');
            if (!firstErrorField) {
                firstErrorField = $('#SignatoryName');
            }
            hasErrors = true;
        }

        // Validate signatory position
        var signatoryPosition = $('#SignatoryPosition').val();
        if (!signatoryPosition || signatoryPosition.trim() === '') {
            e.preventDefault();
            $('#SignatoryPosition').addClass('is-invalid');
            if (!firstErrorField) {
                firstErrorField = $('#SignatoryPosition');
            }
            hasErrors = true;
        }

        // Validate signature - must have signature from pad or file upload or existing
        var signatureFile = $('#signatureFile')[0].files[0];
        var signaturePadData = signaturePad && !signaturePad.isEmpty();
        var existingSignature = existingSignaturePath && existingSignaturePath.length > 0;
        var previewVisible = $('#officialSignaturePreview').is(':visible') && 
                           $('img#officialSignaturePreviewImg').attr('src') && 
                           $('img#officialSignaturePreviewImg').attr('src').length > 0;

        if (!signaturePadData && !signatureFile && !existingSignature && !previewVisible) {
            e.preventDefault();
            showValidationError('officialSignature', 'Please provide an official signature. You can sign using the signature pad or upload a signature image.');
            if (!firstErrorField) {
                firstErrorField = $('#officialSignaturePad');
            }
            hasErrors = true;
        }

        // If there are errors, focus on first error field and scroll to it
        if (hasErrors && firstErrorField) {
            // Scroll to first error field
            $('html, body').animate({
                scrollTop: firstErrorField.offset().top - 100
            }, 500);

            // Focus on the field
            firstErrorField.focus();
            return false;
        }

        // Capture signature from pad if drawn (not empty)
        if (signaturePad && !signaturePad.isEmpty()) {
            try {
                var signatureDataUrl = signaturePad.toDataURL('image/png');
                // Convert data URL to blob and create file
                var byteString = atob(signatureDataUrl.split(',')[1]);
                var mimeString = signatureDataUrl.split(',')[0].split(':')[1].split(';')[0];
                var ab = new ArrayBuffer(byteString.length);
                var ia = new Uint8Array(ab);
                for (var i = 0; i < byteString.length; i++) {
                    ia[i] = byteString.charCodeAt(i);
                }
                var blob = new Blob([ab], { type: mimeString });
                var file = new File([blob], 'official_signature.png', { type: mimeString });

                // Create DataTransfer and add file
                var dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                $('#signatureFile')[0].files = dataTransfer.files;

                console.log('Signature captured from pad');
            } catch (err) {
                console.error('Error capturing signature from pad:', err);
                e.preventDefault();
                showValidationError('officialSignature', 'Error capturing signature. Please try uploading a signature file instead.');
                if (!firstErrorField) {
                    firstErrorField = $('#officialSignaturePad');
                }
                if (firstErrorField) {
                    $('html, body').animate({
                        scrollTop: firstErrorField.offset().top - 100
                    }, 500);
                    firstErrorField.focus();
                }
                return false;
            }
        }

        console.log('Settings form validation passed, submitting...');
        return true;
    });
});

