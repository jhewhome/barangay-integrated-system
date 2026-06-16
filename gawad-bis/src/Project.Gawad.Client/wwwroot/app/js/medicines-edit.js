$(document).ready(function () {
    var medicineNameInput = $('#medicineNameInput');
    var duplicateError = $('#duplicateMedicineError');
    var suggestionsContainer = $('#medicineNameSuggestions');
    var debounceTimer = null;
    var suggestionsList = null;
    
    // Get current medicine ID from global variable set in the view
    var currentMedicineId = typeof window.currentMedicineId !== 'undefined' ? window.currentMedicineId : 
                           (typeof currentMedicineId !== 'undefined' ? currentMedicineId : null);
    
    // Store the original medicine name when page loads
    var originalMedicineName = medicineNameInput.val() || '';

    // Create suggestions list container - position it relative to the form-group
    if (suggestionsList === null) {
        suggestionsList = $('<div id="medicineSuggestionsList" class="list-group" style="position: absolute; z-index: 1000; width: 100%; max-height: 200px; overflow-y: auto; display: none; border: 1px solid #ced4da; border-radius: 0.25rem; border-top-left-radius: 0; border-top-right-radius: 0; background: white; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);"></div>');
        suggestionsContainer.append(suggestionsList);
    }

    // Show autocomplete suggestions as user types
    medicineNameInput.on('input', function() {
        var medicineName = $(this).val();
        
        // Clear duplicate error when user starts typing
        duplicateError.hide();
        $(this).removeClass('is-invalid');
        
        // Clear previous timer
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }
        
        if (!medicineName || medicineName.trim().length < 1) {
            suggestionsList.hide().empty();
            return;
        }
        
        // Debounce the API call
        debounceTimer = setTimeout(function() {
            loadSuggestions(medicineName.trim());
            // Also check for exact duplicate (excluding current medicine)
            checkDuplicateMedicine(medicineName.trim());
        }, 300);
    });

    // Hide suggestions when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#medicineNameInput, #medicineSuggestionsList').length) {
            suggestionsList.hide();
        }
    });

    // Load suggestions from API
    function loadSuggestions(searchTerm) {
        if (!searchTerm || searchTerm.length < 1) {
            suggestionsList.hide().empty();
            return;
        }

        $.ajax({
            url: '/Medicines/GetMedicinesList',
            method: 'GET',
            data: {
                search: searchTerm,
                page: 1,
                itemsPerPage: 10,
                sortColIndex: 0,
                sortColDir: 'asc'
            },
            success: function(response) {
                var dataArray = response.data || response.Data || [];
                
                // Clear previous suggestions
                suggestionsList.empty();
                
                if (dataArray.length === 0) {
                    suggestionsList.hide();
                    return;
                }
                
                // Show suggestions (excluding current medicine if ID is known)
                dataArray.forEach(function(med) {
                    var medId = med.id || med.Id || '';
                    // If ID is an object (ObjectId), get the string value
                    if (typeof medId === 'object' && medId !== null) {
                        medId = medId.toString ? medId.toString() : (medId.value || medId.$oid || '');
                    }
                    
                    // Skip current medicine in suggestions
                    if (currentMedicineId && String(medId) === String(currentMedicineId)) {
                        return;
                    }
                    
                    var medName = med.name || med.Name || '';
                    var medCategory = med.category || med.Category || '';
                    
                    var item = $('<a href="#" class="list-group-item list-group-item-action suggestion-item"></a>')
                        .text(medName + (medCategory ? ' (' + medCategory + ')' : ''))
                        .data('name', medName)
                        .css('cursor', 'pointer');
                    
                    // Highlight matching text
                    if (searchTerm && medName.toLowerCase().includes(searchTerm.toLowerCase())) {
                        var regex = new RegExp('(' + searchTerm + ')', 'gi');
                        var highlightedName = medName.replace(regex, '<strong>$1</strong>');
                        item.html(highlightedName + (medCategory ? ' <small class="text-muted">(' + medCategory + ')</small>' : ''));
                    }
                    
                    item.on('click', function(e) {
                        e.preventDefault();
                        var selectedName = $(this).data('name');
                        medicineNameInput.val(selectedName);
                        suggestionsList.hide();
                        
                        // Check if selected name is a duplicate (excluding current medicine)
                        checkDuplicateMedicine(selectedName);
                    });
                    
                    suggestionsList.append(item);
                });
                
                // Position and show suggestions - position relative to form-group
                var inputPosition = medicineNameInput.position();
                var inputHeight = medicineNameInput.outerHeight();
                var inputWidth = medicineNameInput.outerWidth();
                
                suggestionsList.css({
                    top: inputPosition.top + inputHeight,
                    left: inputPosition.left,
                    width: inputWidth
                }).show();
            },
            error: function() {
                suggestionsList.hide();
            }
        });
    }

    // Check for duplicate medicine name (excluding current medicine)
    function checkDuplicateMedicine(medicineName) {
        if (!medicineName || medicineName.trim() === '') {
            duplicateError.hide();
            medicineNameInput.removeClass('is-invalid');
            return;
        }

        $.ajax({
            url: '/Medicines/GetMedicinesList',
            method: 'GET',
            data: {
                search: medicineName,
                page: 1,
                itemsPerPage: 50,
                sortColIndex: 0,
                sortColDir: 'asc'
            },
            success: function(response) {
                var dataArray = response.data || response.Data || [];
                var exactMatch = dataArray.find(function(med) {
                    var medId = med.id || med.Id || '';
                    // If ID is an object (ObjectId), get the string value
                    if (typeof medId === 'object' && medId !== null) {
                        medId = medId.toString ? medId.toString() : (medId.value || medId.$oid || '');
                    }
                    
                    // Skip current medicine from duplicate check
                    if (currentMedicineId && String(medId) === String(currentMedicineId)) {
                        return false;
                    }
                    
                    var medName = med.name || med.Name || '';
                    return medName.trim().toLowerCase() === medicineName.trim().toLowerCase();
                });

                if (exactMatch) {
                    // Show duplicate error
                    duplicateError.show();
                    medicineNameInput.addClass('is-invalid');
                } else {
                    // Clear duplicate error
                    duplicateError.hide();
                    medicineNameInput.removeClass('is-invalid');
                }
            },
            error: function() {
                // Silently fail
            }
        });
    }

    // Check for duplicate on blur and auto-fill if blank
    medicineNameInput.on('blur', function() {
        setTimeout(function() {
            var medicineName = medicineNameInput.val();
            
            // If field is blank, restore original name
            if (!medicineName || medicineName.trim().length === 0) {
                if (originalMedicineName && originalMedicineName.trim().length > 0) {
                    medicineNameInput.val(originalMedicineName);
                    duplicateError.hide();
                    medicineNameInput.removeClass('is-invalid');
                }
            } else if (medicineName && medicineName.trim().length > 0) {
                checkDuplicateMedicine(medicineName.trim());
            }
            suggestionsList.hide();
        }, 200); // Delay to allow click on suggestion
    });

    // Form validation before submit
    $('form').on('submit', function(e) {
        var medicineName = medicineNameInput.val();
        
        // If name is empty or whitespace, auto-fill with original name
        if (!medicineName || medicineName.trim().length === 0) {
            if (originalMedicineName && originalMedicineName.trim().length > 0) {
                // Auto-fill with original name
                medicineNameInput.val(originalMedicineName);
                medicineName = originalMedicineName;
                duplicateError.hide();
                medicineNameInput.removeClass('is-invalid');
            } else {
                // If no original name exists, show error
                e.preventDefault();
                duplicateError.html('<i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> Medicine name is required and cannot be blank.');
                duplicateError.show();
                medicineNameInput.addClass('is-invalid').focus();
                return false;
            }
        }
        
        medicineName = medicineName.trim();
        
        // Check if it's a duplicate before submitting (excluding current medicine)
        $.ajax({
            url: '/Medicines/GetMedicinesList',
            method: 'GET',
            async: false,
            data: {
                search: medicineName,
                page: 1,
                itemsPerPage: 50,
                sortColIndex: 0,
                sortColDir: 'asc'
            },
            success: function(response) {
                var dataArray = response.data || response.Data || [];
                var exactMatch = dataArray.find(function(med) {
                    var medId = med.id || med.Id || '';
                    // If ID is an object (ObjectId), get the string value
                    if (typeof medId === 'object' && medId !== null) {
                        medId = medId.toString ? medId.toString() : (medId.value || medId.$oid || '');
                    }
                    
                    // Skip current medicine from duplicate check
                    if (currentMedicineId && String(medId) === String(currentMedicineId)) {
                        return false;
                    }
                    
                    var medName = med.name || med.Name || '';
                    return medName.trim().toLowerCase() === medicineName.toLowerCase();
                });

                if (exactMatch) {
                    e.preventDefault();
                    duplicateError.html('<i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> This medicine name already exists in the system. You cannot use a duplicate medicine name.');
                    duplicateError.show();
                    medicineNameInput.addClass('is-invalid').focus();
                    return false;
                }
            }
        });
    });
    
    // Handle Unit of Measure change to show/hide bottle measurement section
    $('#unitOfMeasureSelect').on('change', function() {
        var unitOfMeasure = $(this).val();
        var bottleSection = $('#bottleMeasurementSection');
        var bottleTypeSelect = $('#bottleMeasurementType');
        var bottleValueInput = $('#bottleMeasurementValue');
        var bottleUnitLabel = $('#bottleMeasurementUnit');
        var bottleValueLabel = $('#bottleMeasurementValueLabel');
        
        if (unitOfMeasure === '3' || unitOfMeasure === 'Bottle') {
            // Show bottle measurement section
            bottleSection.show();
            // Make fields optional but visible
            bottleTypeSelect.prop('required', false);
            bottleValueInput.prop('required', false);
        } else {
            // Hide bottle measurement section
            bottleSection.hide();
            // Clear values
            bottleTypeSelect.val('');
            bottleValueInput.val('');
        }
    });
    
    // Handle bottle measurement type change to update label
    $('#bottleMeasurementType').on('change', function() {
        var measurementType = $(this).val();
        var bottleUnitLabel = $('#bottleMeasurementUnit');
        var bottleValueLabel = $('#bottleMeasurementValueLabel');
        
        if (measurementType === 'mg') {
            bottleUnitLabel.text('mg');
            bottleValueLabel.text('MG per Bottle');
        } else if (measurementType === 'ml') {
            bottleUnitLabel.text('ml');
            bottleValueLabel.text('ML per Bottle');
        } else {
            bottleUnitLabel.text('mg/ml');
            bottleValueLabel.text('Measurement Value');
        }
    });
    
    // Initialize on page load
    $('#unitOfMeasureSelect').trigger('change');
    $('#bottleMeasurementType').trigger('change');
});

