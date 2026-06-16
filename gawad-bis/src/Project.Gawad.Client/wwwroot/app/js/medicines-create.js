$(document).ready(function () {
    // Wait a bit to ensure DOM is fully ready
    setTimeout(function() {
        var medicineNameInput = $('#medicineNameInput');
        var duplicateError = $('#duplicateMedicineError');
        var suggestionsContainer = $('#medicineNameSuggestions');
        
        // Check if elements exist before proceeding
        if (medicineNameInput.length === 0 || suggestionsContainer.length === 0) {
            console.warn('Required elements not found, skipping medicine name autocomplete initialization');
            return;
        }
        
        var debounceTimer = null;
        var suggestionsList = null;

        // Create suggestions list container - position it relative to the form-group
        if (suggestionsList === null && suggestionsContainer.length > 0) {
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
            // Also check for exact duplicate
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
                
                // Show suggestions
                dataArray.forEach(function(med) {
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
                        
                        // Show duplicate error since they selected an existing medicine
                        duplicateError.show();
                        medicineNameInput.addClass('is-invalid').focus();
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

        // Check for duplicate medicine name
        function checkDuplicateMedicine(medicineName) {
            if (!medicineName || medicineName.trim() === '') {
                if (duplicateError && duplicateError.length > 0) {
                    duplicateError.hide();
                }
                if (medicineNameInput && medicineNameInput.length > 0) {
                    medicineNameInput.removeClass('is-invalid');
                }
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
                    var medName = med.name || med.Name || '';
                    return medName.trim().toLowerCase() === medicineName.trim().toLowerCase();
                });

                if (exactMatch) {
                    // Show duplicate error
                    if (duplicateError && duplicateError.length > 0) {
                        duplicateError.show();
                    }
                    if (medicineNameInput && medicineNameInput.length > 0) {
                        medicineNameInput.addClass('is-invalid');
                    }
                } else {
                    // Clear duplicate error
                    if (duplicateError && duplicateError.length > 0) {
                        duplicateError.hide();
                    }
                    if (medicineNameInput && medicineNameInput.length > 0) {
                        medicineNameInput.removeClass('is-invalid');
                    }
                }
            },
            error: function() {
                // Silently fail
            }
        });
    }

        // Check for duplicate on blur
        medicineNameInput.on('blur', function() {
            setTimeout(function() {
                if (medicineNameInput && medicineNameInput.length > 0) {
                    var medicineName = medicineNameInput.val();
                    if (medicineName && medicineName.trim().length > 0) {
                        checkDuplicateMedicine(medicineName.trim());
                    }
                }
                if (suggestionsList && suggestionsList.length > 0) {
                    suggestionsList.hide();
                }
            }, 200); // Delay to allow click on suggestion
        });

        // Form validation before submit
        $('form').on('submit', function(e) {
        var medicineName = medicineNameInput.val();
        if (medicineName && medicineName.trim().length > 0) {
            // Check if it's a duplicate before submitting
            $.ajax({
                url: '/Medicines/GetMedicinesList',
                method: 'GET',
                async: false,
                data: {
                    search: medicineName.trim(),
                    page: 1,
                    itemsPerPage: 50,
                    sortColIndex: 0,
                    sortColDir: 'asc'
                },
                success: function(response) {
                    var dataArray = response.data || response.Data || [];
                    var exactMatch = dataArray.find(function(med) {
                        var medName = med.name || med.Name || '';
                        return medName.trim().toLowerCase() === medicineName.trim().toLowerCase();
                    });

                    if (exactMatch) {
                        e.preventDefault();
                        if (duplicateError && duplicateError.length > 0) {
                            duplicateError.show();
                        }
                        if (medicineNameInput && medicineNameInput.length > 0) {
                            medicineNameInput.addClass('is-invalid').focus();
                        }
                        return false;
                    }
                }
            });
        }
    }, 100); // Small delay to ensure DOM is ready
}); // End of document.ready for medicine name autocomplete

// Function to toggle bottle measurement section - defined in global scope
function toggleBottleMeasurementSection(unitOfMeasureSelect) {
        var unitOfMeasure = $(unitOfMeasureSelect).val();
        var selectedText = $(unitOfMeasureSelect).find('option:selected').text().trim().toLowerCase();
        console.log('=== Unit of Measure changed ===');
        console.log('Value:', unitOfMeasure, 'Type:', typeof unitOfMeasure);
        console.log('Text:', selectedText);
        console.log('All options:', $(unitOfMeasureSelect).find('option').map(function() { 
            return $(this).val() + ':' + $(this).text(); 
        }).get());
        
        var bottleSection = $('#bottleMeasurementSection');
        var bottleTypeSelect = $('#bottleMeasurementType');
        var bottleValueInput = $('#bottleMeasurementValue');
        
        var boxContentSection = $('#boxContentSection');
        var boxContentTypeSelect = $('#boxContentType');
        var boxContentValueInput = $('#boxContentValue');
        
        console.log('Bottle section exists:', bottleSection.length > 0);
        console.log('Box content section exists:', boxContentSection.length > 0);
        
        // Check if Bottle is selected - the enum value for Bottle is 3
        // Check if Box is selected - the enum value for Box is 4
        var isBottle = false;
        var isBox = false;
        if (unitOfMeasure) {
            // Convert to number for comparison
            var unitValue = parseInt(unitOfMeasure, 10);
            console.log('Parsed unit value:', unitValue);
            isBottle = unitValue === 3 || 
                       unitOfMeasure === '3' || 
                       unitOfMeasure === 'Bottle' || 
                       selectedText === 'bottle' ||
                       selectedText.includes('bottle');
            isBox = unitValue === 4 || 
                    unitOfMeasure === '4' || 
                    unitOfMeasure === 'Box' || 
                    selectedText === 'box' ||
                    selectedText.includes('box');
        }
        
        console.log('Is Bottle selected?', isBottle);
        console.log('Is Box selected?', isBox);
        
        // Handle Box Content Section
        if (boxContentSection.length > 0) {
            if (isBox) {
                // Show box content section
                console.log('>>> Showing box content section');
                boxContentSection.removeAttr('style');
                boxContentSection.show();
                boxContentSection.css('display', 'block');
                boxContentSection.css('visibility', 'visible');
                boxContentSection.addClass('d-block');
                boxContentSection.removeClass('d-none');
                setTimeout(function() {
                    boxContentSection.attr('style', 'display: block !important; visibility: visible !important;');
                    console.log('✓ Box content section is now visible!');
                }, 100);
                boxContentTypeSelect.prop('required', false);
                boxContentValueInput.prop('required', false);
            } else {
                // Hide box content section
                console.log('>>> Hiding box content section');
                boxContentSection.css('display', 'none');
                boxContentSection.hide();
                boxContentSection.removeClass('d-block');
                boxContentSection.addClass('d-none');
                boxContentTypeSelect.val('');
                boxContentValueInput.val('');
            }
        }
        
        // Handle Bottle Measurement Section
        if (bottleSection.length === 0) {
            console.error('Bottle section element not found!');
            return;
        }
        
        if (isBottle) {
            // Show bottle measurement section - use multiple methods to ensure it shows
            console.log('>>> Showing bottle measurement section');
            
            // Method 1: Remove inline style
            bottleSection.removeAttr('style');
            
            // Method 2: Use jQuery show()
            bottleSection.show();
            
            // Method 3: Set CSS directly
            bottleSection.css('display', 'block');
            bottleSection.css('visibility', 'visible');
            
            // Method 4: Add Bootstrap class
            bottleSection.addClass('d-block');
            bottleSection.removeClass('d-none');
            
            // Method 5: Force with inline style using attr (for !important)
            setTimeout(function() {
                bottleSection.attr('style', 'display: block !important; visibility: visible !important;');
                
                // Verify it's visible
                var displayStyle = bottleSection.css('display');
                var isVisible = bottleSection.is(':visible');
                var computedStyle = window.getComputedStyle ? window.getComputedStyle(bottleSection[0]) : null;
                console.log('Bottle section status - display:', displayStyle, 'isVisible:', isVisible);
                if (computedStyle) {
                    console.log('Computed display:', computedStyle.display);
                }
                
                if (displayStyle === 'none' || !isVisible) {
                    console.error('Bottle section still hidden after all methods!');
                    // Last resort: try to find parent and check
                    var parent = bottleSection.parent();
                    console.log('Parent element:', parent.length > 0 ? parent[0].tagName : 'none');
                } else {
                    console.log('✓ Bottle section is now visible!');
                }
            }, 100);
            
            // Make fields optional but visible
            bottleTypeSelect.prop('required', false);
            bottleValueInput.prop('required', false);
        } else {
            // Hide bottle measurement section
            console.log('>>> Hiding bottle measurement section');
            bottleSection.css('display', 'none');
            bottleSection.hide();
            bottleSection.removeClass('d-block');
            bottleSection.addClass('d-none');
            // Clear values
            bottleTypeSelect.val('');
            bottleValueInput.val('');
        }
    }
    
    // Handle Unit of Measure change to show/hide bottle measurement section
    // Use both direct handler and event delegation for maximum compatibility
    $('#unitOfMeasureSelect').on('change', function() {
        console.log('Direct change handler fired');
        toggleBottleMeasurementSection(this);
    });
    
    // Also use event delegation as backup
    $(document).on('change', '#unitOfMeasureSelect', function() {
        console.log('Delegated change handler fired');
        toggleBottleMeasurementSection(this);
    });
    
    // Handle bottle measurement type change to update label
    $(document).on('change', '#bottleMeasurementType', function() {
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
    
    // Initialize on page load - check if Bottle or Box is already selected
    setTimeout(function() {
        console.log('=== Initializing unit of measure sections ===');
        var unitOfMeasureSelect = $('#unitOfMeasureSelect');
        if (unitOfMeasureSelect.length === 0) {
            console.warn('unitOfMeasureSelect not found during initialization');
            return;
        }
        
        var initialUnitOfMeasure = unitOfMeasureSelect.val();
        var initialText = unitOfMeasureSelect.find('option:selected').text().trim().toLowerCase();
        console.log('Initial Unit of Measure:', initialUnitOfMeasure, 'Text:', initialText);
        
        var isBottle = false;
        var isBox = false;
        if (initialUnitOfMeasure) {
            var unitValue = parseInt(initialUnitOfMeasure, 10);
            isBottle = unitValue === 3 || 
                       initialUnitOfMeasure === '3' || 
                       initialUnitOfMeasure === 'Bottle' ||
                       initialText === 'bottle' ||
                       initialText.includes('bottle');
            isBox = unitValue === 4 || 
                    initialUnitOfMeasure === '4' || 
                    initialUnitOfMeasure === 'Box' ||
                    initialText === 'box' ||
                    initialText.includes('box');
        }
        
        // Initialize Box Content Section
        if (isBox) {
            console.log('Box is initially selected, showing box content section');
            var boxContentSection = $('#boxContentSection');
            if (boxContentSection.length > 0) {
                boxContentSection.removeAttr('style');
                boxContentSection.css('display', 'block');
                boxContentSection.css('visibility', 'visible');
                boxContentSection.show();
                boxContentSection.addClass('d-block');
                boxContentSection.removeClass('d-none');
                boxContentSection.attr('style', 'display: block !important; visibility: visible !important;');
                console.log('✓ Box content section initialized and shown');
            }
        }
        
        // Initialize Bottle Measurement Section
        if (isBottle) {
            console.log('Bottle is initially selected, showing section');
            var bottleSection = $('#bottleMeasurementSection');
            if (bottleSection.length > 0) {
                bottleSection.removeAttr('style');
                bottleSection.css('display', 'block');
                bottleSection.css('visibility', 'visible');
                bottleSection.show();
                bottleSection.addClass('d-block');
                bottleSection.removeClass('d-none');
                bottleSection.attr('style', 'display: block !important; visibility: visible !important;');
                console.log('✓ Bottle section initialized and shown');
            } else {
                console.error('Bottle section not found during initialization!');
            }
        }
        
        // Trigger change to set initial state
        unitOfMeasureSelect.trigger('change');
        var bottleMeasurementType = $('#bottleMeasurementType');
        if (bottleMeasurementType.length > 0) {
            bottleMeasurementType.trigger('change');
        }
    }, 500);
});

