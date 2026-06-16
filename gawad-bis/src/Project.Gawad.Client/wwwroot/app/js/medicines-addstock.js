$(document).ready(function () {
    // Wait a bit to ensure DOM is fully ready
    setTimeout(function() {
        // Initialize Select2 for medicine selection
        var medicineSelect = $('#medicineSelect');
        if (medicineSelect.length === 0) {
            console.error('medicineSelect element not found!');
            return;
        }
        
        // Get a valid dropdown parent - use the form or body, whichever exists
        var dropdownParent = $('#addStockForm').length > 0 ? $('#addStockForm') : (document.body ? $(document.body) : null);
        
        var select2Options = {
            theme: 'bootstrap4',
            placeholder: 'Type to search for a medicine...',
            allowClear: true,
            width: '100%',
            language: {
                searching: function() {
                    return 'Searching medicines...';
                },
                noResults: function() {
                    return 'No medicines found';
                }
            },
            ajax: {
                url: '/Medicines/GetMedicinesList',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    var requestData = {
                        search: params.term || '',
                        page: params.page || 1,
                        itemsPerPage: 10,
                        sortColIndex: 0,
                        sortColDir: 'asc'
                    };
                    return requestData;
                },
                processResults: function (response) {
                    // Handle PaginatedRecords response structure
                    var dataArray = [];
                    
                    // Try different possible response structures
                    if (response.data && Array.isArray(response.data)) {
                        dataArray = response.data;
                    } else if (response.Data && Array.isArray(response.Data)) {
                        dataArray = response.Data;
                    } else if (Array.isArray(response)) {
                        dataArray = response;
                    }
                    
                    if (!Array.isArray(dataArray) || dataArray.length === 0) {
                        return {
                            results: [],
                            pagination: {
                                more: false
                            }
                        };
                    }
                    
                    var hasMore = false;
                    var recordsFiltered = response.RecordsFiltered || response.recordsFiltered || response.RecordsTotal || response.recordsTotal || 0;
                    var pageSize = response.PageSize || response.pageSize || 10;
                    var currentPage = response.PageNumber || response.pageNumber || 1;
                    
                    if (recordsFiltered > 0) {
                        hasMore = (currentPage * pageSize) < recordsFiltered;
                    } else {
                        // Fallback: assume more if we got a full page
                        hasMore = dataArray.length === pageSize;
                    }
                    
                    var results = dataArray.map(function (medicine) {
                        // Handle both camelCase and PascalCase property names
                        var medicineId = medicine.id || medicine.Id || '';
                        var medicineName = medicine.name || medicine.Name || 'Unknown Medicine';
                        var currentStock = medicine.currentStock || medicine.CurrentStock || 0;
                        
                        // If ID is an object (ObjectId), get the string value
                        if (typeof medicineId === 'object' && medicineId !== null) {
                            medicineId = medicineId.toString ? medicineId.toString() : (medicineId.value || medicineId.$oid || '');
                        }
                        
                        // Add stock info to the display text
                        var displayText = medicineName;
                        if (currentStock > 0) {
                            displayText += ' (Current Stock: ' + currentStock + ')';
                        }
                        
                        return {
                            id: String(medicineId),
                            text: displayText,
                            data: medicine
                        };
                    });
                    
                    return {
                        results: results,
                        pagination: {
                            more: hasMore
                        }
                    };
                },
                error: function(xhr, status, error) {
                    console.error('Error loading medicines:', error);
                },
                cache: false
            }
        };
        
        // Only add dropdownParent if we have a valid element
        if (dropdownParent && dropdownParent.length > 0) {
            select2Options.dropdownParent = dropdownParent;
        }
        
        medicineSelect.select2(select2Options);
    }, 100); // Small delay to ensure DOM is ready

    // Disable Select2 if the select element is disabled (to prevent changing medicine when adding stock)
    if ($('#medicineSelect').prop('disabled')) {
        $('#medicineSelect').prop('disabled', true).trigger('change');
        $('#medicineSelect').next('.select2-container').addClass('select2-container-disabled');
    }

    // When a medicine is selected, update the hidden field and check for existing stock
    $('#medicineSelect').on('select2:select', function(e) {
        var selectedData = e.params.data;
        var medicineId = selectedData.id;
        var medicineData = selectedData.data;
        var currentStock = medicineData.currentStock || medicineData.CurrentStock || 0;
        var unitOfMeasure = (medicineData.unitOfMeasure || medicineData.UnitOfMeasure || '').toString();
        // Try multiple possible property names for unit price (camelCase, PascalCase)
        var unitPrice = medicineData.unitPrice !== undefined ? medicineData.unitPrice : 
                       (medicineData.UnitPrice !== undefined ? medicineData.UnitPrice : null);
        var bottleMeasurementType = medicineData.bottleMeasurementType || medicineData.BottleMeasurementType || null;
        var bottleMeasurementValue = medicineData.bottleMeasurementValue || medicineData.BottleMeasurementValue || null;
        
        // Update hidden field
        $('#MedicineId').val(medicineId);
        
        // Update cancel link if we have a medicine ID
        var cancelLink = $('#cancelLink');
        if (medicineId) {
            cancelLink.attr('href', '/Medicines/Details/' + medicineId);
        } else {
            cancelLink.attr('href', '/Medicines/Index');
        }
        
        // Show warning if medicine already has stock
        if (currentStock > 0) {
            $('#stockWarning').show();
        } else {
            $('#stockWarning').hide();
        }
        
        // Update cost label and pre-fill unit price based on medicine's Unit of Measure
        // If unitPrice is not available in list data, fetch medicine details
        if ((unitPrice == null || unitPrice === undefined || unitPrice === '') && medicineId) {
            // Fetch medicine details to get unit price
            $.ajax({
                url: '/Medicines/GetMedicineDetailsJson/' + medicineId,
                method: 'GET',
                success: function(response) {
                    if (response.success && response.unitPrice != null) {
                        var detailUnitPrice = response.unitPrice;
                        var detailUnitOfMeasure = (response.unitOfMeasure || unitOfMeasure || '').toString();
                        updateCostLabelAndPrice(detailUnitOfMeasure, detailUnitPrice);
                    } else {
                        // Fallback: use what we have
                        updateCostLabelAndPrice(unitOfMeasure, unitPrice);
                    }
                },
                error: function() {
                    // Fallback: use what we have
                    updateCostLabelAndPrice(unitOfMeasure, unitPrice);
                }
            });
        } else {
            updateCostLabelAndPrice(unitOfMeasure, unitPrice);
        }
        
        // Auto-select input method based on medicine's Unit of Measure
        // Handle various enum representations: 'Box', 'box', '4', 4, 'Bottle', 'bottle', '3', 3
        var unitMeasureLower = unitOfMeasure.toString().toLowerCase();
        var unitMeasureNum = parseInt(unitOfMeasure) || unitOfMeasure;
        
        // Hide the input method selection section - it will be auto-assigned
        $('#inputMethodSection').hide();
        
        if (unitMeasureLower === 'box' || unitMeasureNum === 4 || unitMeasureNum === '4') {
            console.log('Auto-selecting "By Box" input method for medicine with UnitOfMeasure:', unitOfMeasure);
            $('#inputMethodBoxes').prop('checked', true).prop('disabled', true);
            switchInputMethod('boxes');
        } else if (unitMeasureLower === 'bottle' || unitMeasureNum === 3 || unitMeasureNum === '3') {
            console.log('Auto-selecting "By Bottle" input method for medicine with UnitOfMeasure:', unitOfMeasure);
            $('#inputMethodBottles').prop('checked', true).prop('disabled', true);
            switchInputMethod('bottles');
            
            // Display bottle measurement info (MG/ML) as informational only
            var bottleInfoText = '';
            if (bottleMeasurementType && bottleMeasurementValue) {
                var measureType = bottleMeasurementType.toLowerCase();
                var measureValue = parseFloat(bottleMeasurementValue) || 0;
                if (measureType === 'mg') {
                    bottleInfoText = 'This medicine contains ' + measureValue + 'mg per bottle (informational only)';
                } else if (measureType === 'ml') {
                    bottleInfoText = 'This medicine contains ' + measureValue + 'ml per bottle (informational only)';
                }
            }
            if (bottleInfoText) {
                $('#bottleInfoText').text(bottleInfoText);
                $('#bottleInfoDisplay').show();
            } else {
                $('#bottleInfoDisplay').hide();
            }
        } else {
            // Default to boxes if unit is not box or bottle
            console.log('Unknown UnitOfMeasure:', unitOfMeasure, '- defaulting to "By Box"');
            $('#inputMethodBoxes').prop('checked', true).prop('disabled', true);
            switchInputMethod('boxes');
            $('#bottleInfoDisplay').hide();
        }
    });

    // Function to update cost label and pre-fill unit price
    function updateCostLabelAndPrice(unitOfMeasure, unitPrice) {
        var unitMeasure = (unitOfMeasure || '').toString().toLowerCase();
        var costInput = $('#CostPerUnit');
        var costLabel = $('#costUnitLabel');
        var unitPriceDesc = $('#unitPriceDescription');
        
        // Update label based on unit of measure
        if (unitMeasure === 'box' || unitMeasure === '4') {
            costLabel.text('Unit Price (per box)');
            unitPriceDesc.text('Enter the price per box. If left empty, the medicine\'s default unit price will be used.');
        } else if (unitMeasure === 'bottle' || unitMeasure === '3') {
            costLabel.text('Unit Price (per bottle)');
            unitPriceDesc.text('Enter the price per bottle. If left empty, the medicine\'s default unit price will be used.');
        } else if (unitMeasure === 'tablet' || unitMeasure === '1') {
            costLabel.text('Unit Price (per tablet)');
            unitPriceDesc.text('Enter the price per tablet. If left empty, the medicine\'s default unit price will be used.');
        } else if (unitMeasure === 'capsule' || unitMeasure === '2') {
            costLabel.text('Unit Price (per capsule)');
            unitPriceDesc.text('Enter the price per capsule. If left empty, the medicine\'s default unit price will be used.');
        } else if (unitMeasure) {
            costLabel.text('Unit Price (per ' + unitMeasure + ')');
            unitPriceDesc.text('Enter the price per ' + unitMeasure + '. If left empty, the medicine\'s default unit price will be used.');
        } else {
            costLabel.text('Unit Price');
            unitPriceDesc.text('Enter the price per unit. If left empty, the medicine\'s default unit price will be used.');
        }
        
        // Pre-fill unit price if available, but allow user to override
        if (unitPrice != null && unitPrice !== undefined && unitPrice !== '') {
            var priceValue = parseFloat(unitPrice);
            if (!isNaN(priceValue) && priceValue >= 0) {
                // Only set if the field is empty (don't overwrite user input)
                if (!costInput.val() || costInput.val() === '') {
                    costInput.val(priceValue.toFixed(2));
                }
            }
        }
        
        // Update summary when cost changes - use event delegation for reliability
        // Include keypress for immediate feedback as user types
        $(document).off('keypress input keyup change paste', '#CostPerUnit').on('keypress input keyup change paste', '#CostPerUnit', function() {
            var costValue = $(this).val();
            console.log('CostPerUnit changed to:', costValue);
            // Update summary immediately
            updateSummary();
            // Force update again after a small delay to ensure DOM is updated
            setTimeout(function() {
                updateSummary();
            }, 10);
        });
        
        updateSummary();
    }

    // When selection is cleared, clear the hidden field and hide warning
    $('#medicineSelect').on('select2:clear', function(e) {
        $('#MedicineId').val('');
        $('#stockWarning').hide();
        $('#cancelLink').attr('href', '/Medicines/Index');
    });
    
    // Function to handle input method switching
    function switchInputMethod(inputMethod) {
        console.log('switchInputMethod called with:', inputMethod);
        
        // Hide all sections first - use attr to ensure they're hidden
        $('#piecesInputSection').attr('style', 'display: none !important;').hide();
        $('#boxesInputSection').attr('style', 'display: none !important;').hide();
        $('#bottlesInputSection').attr('style', 'display: none !important;').hide();
        
        // Note: "pieces" input method is hidden - only boxes and bottles are available
        if (inputMethod === 'pieces') {
            // If somehow pieces is selected, default to boxes
            console.warn('Pieces input method selected but hidden, defaulting to boxes');
            $('#inputMethodBoxes').prop('checked', true);
            inputMethod = 'boxes';
        }
        
        if (inputMethod === 'boxes') {
            // Hide bottles section
            $('#bottlesInputSection').removeClass('show-bottles').removeAttr('style').hide();
            // Show boxes section
            $('#boxesInputSection').attr('style', 'display: block !important;').show();
            $('#quantityPieces').prop('required', false).removeAttr('required');
            $('#numberOfBoxes, #piecesPerBox').prop('required', true);
            $('#numberOfBottles, #piecesPerBottle').prop('required', false);
            $('#costUnitLabel').text('Unit Price (per box)');
            $('#inputUnitType').val('boxes');
            // Set default values if empty
            if (!$('#numberOfBoxes').val()) {
                $('#numberOfBoxes').val('1');
            }
            if (!$('#piecesPerBox').val()) {
                $('#piecesPerBox').val('100');
            }
            // Recalculate total pieces when switching to boxes
            calculateTotalPiecesFromBoxes();
            updateSummary();
        } else if (inputMethod === 'bottles') {
            console.log('Switching to bottles - showing bottles section');
            var bottlesSection = $('#bottlesInputSection');
            console.log('Bottles section element found:', bottlesSection.length > 0);
            
            if (bottlesSection.length === 0) {
                console.error('Bottles section element not found!');
                return;
            }
            
            // Remove inline style and use CSS class
            bottlesSection.removeAttr('style');
            bottlesSection.addClass('show-bottles');
            bottlesSection.addClass('d-block');
            bottlesSection.removeClass('d-none');
            bottlesSection.show();
            
            // Also set CSS directly as backup
            bottlesSection.css({
                'display': 'block',
                'visibility': 'visible',
                'opacity': '1'
            });
            
            // Verify it's visible
            setTimeout(function() {
                var displayStyle = bottlesSection.css('display');
                var isVisible = bottlesSection.is(':visible');
                var hasClass = bottlesSection.hasClass('show-bottles');
                console.log('Bottles section - display:', displayStyle, 'isVisible:', isVisible, 'hasClass:', hasClass);
                
                if (displayStyle === 'none' || !isVisible) {
                    console.error('Bottles section still hidden! Trying force method...');
                    // Force with inline style
                    bottlesSection.attr('style', 'display: block !important; visibility: visible !important; opacity: 1 !important;');
                }
            }, 10);
            
            $('#quantityPieces').prop('required', false).removeAttr('required');
            $('#numberOfBottles, #piecesPerBottle').prop('required', true);
            $('#numberOfBoxes, #piecesPerBox').prop('required', false);
            $('#costUnitLabel').text('Unit Price (per bottle)');
            $('#inputUnitType').val('bottles');
            // Set default values if empty
            if (!$('#numberOfBottles').val()) {
                $('#numberOfBottles').val('1');
            }
            if (!$('#piecesPerBottle').val()) {
                $('#piecesPerBottle').val('1');
            }
            // Recalculate total pieces when switching to bottles
            calculateTotalPiecesFromBottles();
            updateSummary();
        }
    }
    
    // Handle input method change (pieces vs boxes vs bottles)
    $('input[name="inputMethod"]').on('change', function() {
        var inputMethod = $(this).val();
        console.log('Input method radio changed to:', inputMethod);
        switchInputMethod(inputMethod);
    });
    
    // Also handle clicks on the radio buttons (for immediate feedback)
    $('input[name="inputMethod"]').on('click', function() {
        var inputMethod = $(this).val();
        console.log('Input method radio clicked:', inputMethod);
        // Use setTimeout to ensure the radio is checked before switching
        setTimeout(function() {
            switchInputMethod(inputMethod);
        }, 10);
    });

    // Calculate total pieces from boxes
    function calculateTotalPiecesFromBoxes() {
        console.log('calculateTotalPiecesFromBoxes called');
        var numberOfBoxesEl = $('#numberOfBoxes');
        var piecesPerBoxEl = $('#piecesPerBox');
        var totalPiecesFromBoxesEl = $('#totalPiecesFromBoxes');
        
        // Check if elements exist
        if (numberOfBoxesEl.length === 0 || piecesPerBoxEl.length === 0 || totalPiecesFromBoxesEl.length === 0) {
            console.error('calculateTotalPiecesFromBoxes: Required elements not found! numberOfBoxes:', numberOfBoxesEl.length, 'piecesPerBox:', piecesPerBoxEl.length, 'totalPiecesFromBoxes:', totalPiecesFromBoxesEl.length);
            return;
        }
        
        var numberOfBoxes = parseFloat(numberOfBoxesEl.val()) || 0;
        var piecesPerBox = parseFloat(piecesPerBoxEl.val()) || 0;
        console.log('numberOfBoxes:', numberOfBoxes, 'piecesPerBox:', piecesPerBox);
        
        var totalPieces = numberOfBoxes * piecesPerBox;
        var totalPiecesRounded = Math.floor(totalPieces);
        console.log('Total pieces calculated:', totalPiecesRounded);
        
        // Update the total pieces field - use multiple methods to ensure it updates
        totalPiecesFromBoxesEl.val(totalPiecesRounded);
        totalPiecesFromBoxesEl.attr('value', totalPiecesRounded);
        // Also try setting the property directly
        if (totalPiecesFromBoxesEl[0]) {
            totalPiecesFromBoxesEl[0].value = totalPiecesRounded;
        }
        console.log('totalPiecesFromBoxes field updated to:', totalPiecesFromBoxesEl.val(), 'Element:', totalPiecesFromBoxesEl[0]);
        
        // Update both the visible and hidden quantity fields
        $('#quantityPieces').val(totalPiecesRounded);
        $('#quantityHidden').val(totalPiecesRounded);
        // Store unit info for cost calculation
        $('#inputUnitCount').val(numberOfBoxes);
        console.log('Fields updated - quantityHidden:', $('#quantityHidden').val(), 'inputUnitCount:', $('#inputUnitCount').val());
        // Update summary
        updateSummary();
    }

    // Calculate total pieces from bottles (always uses pieces per bottle)
    function calculateTotalPiecesFromBottles() {
        console.log('calculateTotalPiecesFromBottles called');
        var numberOfBottles = parseFloat($('#numberOfBottles').val()) || 0;
        var piecesPerBottle = parseFloat($('#piecesPerBottle').val()) || 0;
        console.log('numberOfBottles:', numberOfBottles, 'piecesPerBottle:', piecesPerBottle);
        var totalValue = numberOfBottles * piecesPerBottle;
        var totalPiecesRounded = Math.floor(totalValue);
        console.log('Total pieces calculated:', totalPiecesRounded);
        
        $('#totalPiecesFromBottles').val(totalPiecesRounded);
        $('#quantityPieces').val(totalPiecesRounded);
        $('#quantityHidden').val(totalPiecesRounded);
        
        // Store unit info for cost calculation
        $('#inputUnitCount').val(numberOfBottles);
        console.log('Fields updated - quantityHidden:', $('#quantityHidden').val(), 'inputUnitCount:', $('#inputUnitCount').val());
        // Update summary
        updateSummary();
    }
    
    // Update summary section with calculated totals
    function updateSummary() {
        var inputMethod = $('input[name="inputMethod"]:checked').val();
        
        // If no input method selected, default to the checked one (should be "boxes" by default)
        if (!inputMethod) {
            var checkedRadio = $('input[name="inputMethod"]:checked');
            if (checkedRadio.length > 0) {
                inputMethod = checkedRadio.val();
            } else {
                // If nothing is checked, check "boxes" by default
                $('#inputMethodBoxes').prop('checked', true);
                inputMethod = 'boxes';
            }
        }
        
        // Get CostPerUnit from input field (user can enter or it's pre-filled from medicine)
        var costPerUnit = 0;
        var costInput = $('#CostPerUnit');
        console.log('updateSummary - CostPerUnit input value:', costInput.val(), 'Element found:', costInput.length > 0); // Debug
        
        if (costInput.length > 0) {
            var costValue = costInput.val();
            if (costValue && costValue.trim() !== '') {
                costPerUnit = parseFloat(costValue) || 0;
                console.log('updateSummary - costPerUnit parsed:', costPerUnit); // Debug
            } else {
                console.log('updateSummary - CostPerUnit is empty'); // Debug
            }
        } else {
            console.error('updateSummary - CostPerUnit input element not found!'); // Debug
        }
        
        var quantity = parseFloat($('#quantityHidden').val()) || parseFloat($('#quantityPieces').val()) || 0;
        var unitCount = parseFloat($('#inputUnitCount').val()) || 0;
        
        var totalAmount = 0;
        var calculationText = '';
        
        // Update input method display
        // Note: "pieces" input method is hidden - only boxes and bottles are available
        if (inputMethod === 'pieces') {
            // If somehow pieces is selected, default to boxes
            console.warn('Pieces input method in updateSummary, defaulting to boxes');
            inputMethod = 'boxes';
            $('#inputMethodBoxes').prop('checked', true);
        }
        
        if (inputMethod === 'boxes') {
            var numberOfBoxes = parseFloat($('#numberOfBoxes').val()) || 0;
            
            // PRICE CALCULATION FORMULA: Total Amount = Unit Price × Number of Boxes
            if (costPerUnit > 0 && numberOfBoxes > 0) {
                totalAmount = costPerUnit * numberOfBoxes;
                calculationText = '₱' + costPerUnit.toFixed(2) + ' × ' + numberOfBoxes.toLocaleString('en-US', { maximumFractionDigits: 0 }) + ' box(es) = ₱' + totalAmount.toFixed(2);
            } else {
                totalAmount = 0;
                if (costPerUnit <= 0) {
                    calculationText = 'Enter unit price above';
                } else {
                    calculationText = 'Enter number of boxes above';
                }
            }
        } else if (inputMethod === 'bottles') {
            var numberOfBottles = parseFloat($('#numberOfBottles').val()) || 0;
            
            // PRICE CALCULATION FORMULA: Total Amount = Unit Price × Number of Bottles
            if (costPerUnit > 0 && numberOfBottles > 0) {
                totalAmount = costPerUnit * numberOfBottles;
                calculationText = '₱' + costPerUnit.toFixed(2) + ' × ' + numberOfBottles.toLocaleString('en-US', { maximumFractionDigits: 0 }) + ' bottle(s) = ₱' + totalAmount.toFixed(2);
            } else {
                totalAmount = 0;
                if (costPerUnit <= 0) {
                    calculationText = 'Enter unit price above';
                } else {
                    calculationText = 'Enter number of bottles above';
                }
            }
        } else {
            // Default to boxes if input method is not recognized
            var numberOfBoxes = parseFloat($('#numberOfBoxes').val()) || 0;
            if (costPerUnit > 0 && numberOfBoxes > 0) {
                totalAmount = costPerUnit * numberOfBoxes;
                calculationText = '₱' + costPerUnit.toFixed(2) + ' × ' + numberOfBoxes.toLocaleString('en-US', { maximumFractionDigits: 0 }) + ' box(es) = ₱' + totalAmount.toFixed(2);
            } else {
                totalAmount = 0;
                calculationText = 'Enter values above to see calculation';
            }
        }
        
        // Update summary display
        var summaryTotalAmountEl = $('#summaryTotalAmount');
        var summaryCalculationEl = $('#summaryCalculation');
        
        if (summaryTotalAmountEl.length > 0) {
            // Update total amount display - force immediate update
            var formattedAmount = '₱' + totalAmount.toFixed(2);
            summaryTotalAmountEl.text(formattedAmount);
            console.log('Updated summaryTotalAmount to:', formattedAmount, 'from costPerUnit:', costPerUnit, 'unitCount:', unitCount);
            
            // Update calculation text
            if (summaryCalculationEl.length > 0) {
                if (calculationText && totalAmount > 0) {
                    summaryCalculationEl.text(calculationText);
                } else {
                    if (costPerUnit > 0) {
                        if (inputMethod === 'boxes') {
                            summaryCalculationEl.text('Enter number of boxes above to see calculation');
                        } else if (inputMethod === 'bottles') {
                            summaryCalculationEl.text('Enter number of bottles above to see calculation');
                        } else {
                            summaryCalculationEl.text('Enter values above to see calculation');
                        }
                    } else {
                        summaryCalculationEl.text('Select a medicine to see unit price');
                    }
                }
            }
        } else {
            console.error('summaryTotalAmount element not found! Check if the element exists in the DOM.');
        }
        
        console.log('updateSummary - Summary updated. Input Method:', inputMethod, 'Quantity:', quantity, 'Unit Count:', unitCount, 'CostPerUnit:', costPerUnit); // Debug
    }

    // Function to attach all event listeners for instant summary updates
    function attachSummaryEventListeners() {
        console.log('attachSummaryEventListeners called');
        
        // Use event delegation to ensure listeners work even if elements are dynamically shown/hidden
        // Recalculate when boxes or pieces per box changes - instant update with input/keyup for real-time feedback
        $(document).off('input keyup change', '#numberOfBoxes, #piecesPerBox').on('input keyup change', '#numberOfBoxes, #piecesPerBox', function() {
            console.log('Box input changed:', $(this).attr('id'), 'value:', $(this).val());
            var inputMethod = $('input[name="inputMethod"]:checked').val() || 'boxes';
            if (inputMethod === 'boxes') {
                calculateTotalPiecesFromBoxes();
            } else {
                // Update summary even if not boxes method for instant feedback
                updateSummary();
            }
        });

        // Recalculate when bottles or pieces per bottle changes - instant update
        $(document).off('input keyup change', '#numberOfBottles, #piecesPerBottle').on('input keyup change', '#numberOfBottles, #piecesPerBottle', function() {
            console.log('Bottle input changed:', $(this).attr('id'), 'value:', $(this).val());
            var inputMethod = $('input[name="inputMethod"]:checked').val() || 'bottles';
            if (inputMethod === 'bottles') {
                calculateTotalPiecesFromBottles();
            } else {
                // Update summary even if not bottles method for instant feedback
                updateSummary();
            }
        });
        
        // Update summary when CostPerUnit changes - instant update on keypress, input, keyup, and change
        $(document).off('keypress input keyup change paste', '#CostPerUnit').on('keypress input keyup change paste', '#CostPerUnit', function() {
            var costValue = $(this).val();
            console.log('CostPerUnit changed to:', costValue);
            // Update summary immediately
            updateSummary();
            // Also force update the summaryTotalAmount element directly for instant feedback
            setTimeout(function() {
                updateSummary();
            }, 10);
        });
        
        // Update summary when quantity (pieces) changes - instant update
        $(document).off('input keyup change', '#quantityPieces').on('input keyup change', '#quantityPieces', function() {
            var inputMethod = $('input[name="inputMethod"]:checked').val();
            if (inputMethod === 'pieces') {
                var quantity = parseFloat($(this).val()) || 0;
                $('#quantityHidden').val(Math.floor(quantity));
                $('#inputUnitCount').val(Math.floor(quantity));
            }
            // Always update summary for instant feedback
            updateSummary();
        });
        
        // Also update summary when input method radio buttons change
        $(document).off('change click', 'input[name="inputMethod"]').on('change click', 'input[name="inputMethod"]', function() {
            console.log('Input method radio changed to:', $(this).val(), 'updating summary');
            setTimeout(function() {
                updateSummary();
            }, 50);
        });
    }
    
    // Attach event listeners immediately
    attachSummaryEventListeners();
    
    // Also call updateSummary when medicine is selected (this is already handled in the Select2 change handler)
    // Initialize summary on page load with a delay to ensure DOM is ready
    setTimeout(function() {
        console.log('Initializing summary on page load');
        // Ensure event listeners are attached
        attachSummaryEventListeners();
        // Trigger initial calculation
        var inputMethod = $('input[name="inputMethod"]:checked').val() || 'boxes';
        if (inputMethod === 'boxes') {
            calculateTotalPiecesFromBoxes();
        } else if (inputMethod === 'bottles') {
            calculateTotalPiecesFromBottles();
        }
        updateSummary();
    }, 300);

    // Verify form exists and attach submit handler
    var $form = $('#addStockForm');
    if ($form.length === 0) {
        console.error('ERROR: Form #addStockForm not found!');
    } else {
        console.log('Form #addStockForm found, attaching submit handler');
    }
    
    // Add click handler to submit button for debugging
    $(document).on('click', '#addStockForm button[type="submit"]', function(e) {
        console.log('=== SUBMIT BUTTON CLICKED ===');
        console.log('Form element exists:', $('#addStockForm').length > 0);
        console.log('Form action:', $('#addStockForm').attr('action'));
        console.log('Form method:', $('#addStockForm').attr('method'));
        console.log('Button type:', $(this).attr('type'));
    });
    
    // Form validation before submit - use event delegation to ensure it's attached
    $(document).on('submit', '#addStockForm', function(e) {
        console.log('=== FORM SUBMIT EVENT TRIGGERED ===');
        console.log('Event object:', e);
        console.log('Form element:', this);
        
        var medicineId = $('#MedicineId').val();
        console.log('MedicineId:', medicineId);
        
        if (!medicineId || medicineId === '') {
            console.error('No medicine selected');
            e.preventDefault();
            alert('Please select a medicine before adding stock.');
            $('#medicineSelect').focus();
            return false;
        }

        // Ensure quantity is set correctly based on input method
        var inputMethod = $('input[name="inputMethod"]:checked').val();
        
        // If no input method is checked, default to boxes
        if (!inputMethod) {
            inputMethod = 'boxes';
            $('#inputMethodBoxes').prop('checked', true);
            console.log('No input method selected, defaulting to boxes');
        }
        
        console.log('Input method:', inputMethod);
        if (inputMethod === 'boxes') {
            // Calculate and set total pieces from boxes
            var numberOfBoxes = parseFloat($('#numberOfBoxes').val()) || 0;
            var piecesPerBox = parseFloat($('#piecesPerBox').val()) || 0;
            
            console.log('Boxes validation - numberOfBoxes:', numberOfBoxes, 'piecesPerBox:', piecesPerBox);
            
            if (numberOfBoxes <= 0) {
                console.error('Validation failed: numberOfBoxes <= 0');
                e.preventDefault();
                alert('Please enter a valid number of boxes (greater than 0).');
                $('#numberOfBoxes').focus();
                return false;
            }
            
            if (piecesPerBox <= 0) {
                console.error('Validation failed: piecesPerBox <= 0');
                e.preventDefault();
                alert('Please enter a valid number of pieces per box (greater than 0).');
                $('#piecesPerBox').focus();
                return false;
            }
            
            var totalPieces = numberOfBoxes * piecesPerBox;
            var totalPiecesRounded = Math.floor(totalPieces);
            console.log('Calculated total pieces:', totalPieces, 'Rounded:', totalPiecesRounded);
            
            if (totalPiecesRounded <= 0) {
                console.error('Validation failed: totalPiecesRounded <= 0');
                e.preventDefault();
                alert('Total pieces must be greater than 0. Please check your input.');
                return false;
            }
            
            // Set quantity in all fields
            $('#quantityPieces').val(totalPiecesRounded);
            $('#quantityHidden').val(totalPiecesRounded);
            $('#inputUnitType').val('boxes');
            $('#inputUnitCount').val(numberOfBoxes);
            
            console.log('Boxes - Set quantityHidden to:', totalPiecesRounded, 'inputUnitCount to:', numberOfBoxes);
        } else if (inputMethod === 'bottles') {
            // Calculate and set total from bottles
            var numberOfBottles = parseFloat($('#numberOfBottles').val()) || 0;
            var piecesPerBottle = parseFloat($('#piecesPerBottle').val()) || 0;
            
            console.log('Bottles validation - numberOfBottles:', numberOfBottles, 'piecesPerBottle:', piecesPerBottle);
            
            if (numberOfBottles <= 0) {
                console.error('Validation failed: numberOfBottles <= 0');
                e.preventDefault();
                alert('Please enter a valid number of bottles (greater than 0).');
                $('#numberOfBottles').focus();
                return false;
            }
            
            if (piecesPerBottle <= 0) {
                console.error('Validation failed: piecesPerBottle <= 0');
                e.preventDefault();
                alert('Please enter a valid number of pieces per bottle (greater than 0).');
                $('#piecesPerBottle').focus();
                return false;
            }
            
            var totalPieces = numberOfBottles * piecesPerBottle;
            var totalPiecesRounded = Math.floor(totalPieces);
            console.log('Calculated total pieces:', totalPieces, 'Rounded:', totalPiecesRounded);
            
            if (totalPiecesRounded <= 0) {
                console.error('Validation failed: totalPiecesRounded <= 0');
                e.preventDefault();
                alert('Total pieces must be greater than 0. Please check your input.');
                return false;
            }
            
            // Set quantity in all fields
            $('#quantityPieces').val(totalPiecesRounded);
            $('#quantityHidden').val(totalPiecesRounded);
            $('#inputUnitType').val('bottles');
            $('#inputUnitCount').val(numberOfBottles);
            
            console.log('Bottles - Set quantityHidden to:', totalPiecesRounded, 'inputUnitCount to:', numberOfBottles);
        } else {
            // Validate pieces input
            var pieces = parseFloat($('#quantityPieces').val()) || 0;
            if (pieces <= 0) {
                e.preventDefault();
                alert('Please enter a valid quantity (must be at least 1 piece).');
                $('#quantityPieces').focus();
                return false;
            }
            
            // Also set hidden field for consistency
            $('#quantityHidden').val(Math.floor(pieces));
            $('#inputUnitType').val('pieces');
            $('#inputUnitCount').val(pieces);
        }

        // Ensure quantity is a whole number (pieces only)
        var inputMethod = $('input[name="inputMethod"]:checked').val();
        if (inputMethod === 'pieces' || inputMethod === 'bottles') {
            var quantity = parseFloat($('#quantityPieces').val()) || parseFloat($('#quantityHidden').val()) || 0;
            if (quantity !== Math.floor(quantity)) {
                e.preventDefault();
                alert('Quantity must be a whole number (pieces only). Please round to the nearest whole number.');
                $('#quantityPieces').focus();
                return false;
            }
        }
        
        // Remove required attribute from hidden quantityPieces field to prevent browser validation error
        $('#quantityPieces').removeAttr('required').prop('required', false);
        
        // Disable browser validation on the hidden quantity field
        $('#quantityHidden').removeAttr('required').removeAttr('data-val').attr('data-val', 'false');
        
        // Final check: ensure hidden quantity field has the correct value
        var finalQuantity = parseFloat($('#quantityHidden').val()) || parseFloat($('#quantityPieces').val()) || 0;
        console.log('Final quantity:', finalQuantity);
        console.log('Input unit type:', $('#inputUnitType').val());
        console.log('Input unit count:', $('#inputUnitCount').val());
        
        // If quantity is still 0, try to recalculate from current input method
        if (finalQuantity <= 0) {
            console.warn('Quantity is 0, attempting to recalculate...');
            var currentInputMethod = $('input[name="inputMethod"]:checked').val() || 'boxes';
            
            if (currentInputMethod === 'boxes') {
                var boxes = parseFloat($('#numberOfBoxes').val()) || 0;
                var piecesPerBox = parseFloat($('#piecesPerBox').val()) || 0;
                if (boxes > 0 && piecesPerBox > 0) {
                    finalQuantity = Math.floor(boxes * piecesPerBox);
                    $('#quantityHidden').val(finalQuantity);
                    $('#inputUnitType').val('boxes');
                    $('#inputUnitCount').val(boxes);
                    console.log('Recalculated from boxes:', finalQuantity);
                }
            } else if (currentInputMethod === 'bottles') {
                var bottles = parseFloat($('#numberOfBottles').val()) || 0;
                var piecesPerBottle = parseFloat($('#piecesPerBottle').val()) || 0;
                if (bottles > 0 && piecesPerBottle > 0) {
                    finalQuantity = Math.floor(bottles * piecesPerBottle);
                    $('#quantityHidden').val(finalQuantity);
                    $('#inputUnitType').val('bottles');
                    $('#inputUnitCount').val(bottles);
                    console.log('Recalculated from bottles:', finalQuantity);
                }
            }
        }
        
        if (finalQuantity <= 0) {
            console.error('Quantity validation failed:', finalQuantity);
            e.preventDefault();
            alert('Quantity must be greater than 0. Please enter valid values for boxes/bottles and pieces per box/bottle.');
            return false;
        }
        
        // Ensure quantityHidden has a valid value (greater than 0) to pass server-side validation
        if (finalQuantity > 0) {
            $('#quantityHidden').val(finalQuantity);
            console.log('Set quantityHidden to:', finalQuantity);
        }
        
        // Ensure inputUnitType and inputUnitCount are set
        if (!$('#inputUnitType').val()) {
            var defaultInputMethod = $('input[name="inputMethod"]:checked').val() || 'boxes';
            $('#inputUnitType').val(defaultInputMethod);
            console.log('Set inputUnitType to:', defaultInputMethod);
        }
        
        if (!$('#inputUnitCount').val() || $('#inputUnitCount').val() === '0') {
            var defaultUnitCount = 1;
            if ($('#inputUnitType').val() === 'boxes') {
                defaultUnitCount = parseFloat($('#numberOfBoxes').val()) || 1;
            } else if ($('#inputUnitType').val() === 'bottles') {
                defaultUnitCount = parseFloat($('#numberOfBottles').val()) || 1;
            }
            $('#inputUnitCount').val(defaultUnitCount);
            console.log('Set inputUnitCount to:', defaultUnitCount);
        }

        console.log('=== ALL VALIDATIONS PASSED ===');
        console.log('Form will submit now - NOT preventing default');
        console.log('Final form data:');
        console.log('  MedicineId:', $('#MedicineId').val());
        console.log('  Quantity:', $('#quantityHidden').val());
        console.log('  InputUnitType:', $('#inputUnitType').val());
        console.log('  InputUnitCount:', $('#inputUnitCount').val());
        console.log('  CostPerUnit:', $('#CostPerUnit').val());
        console.log('  ExpiryDate:', $('#ExpiryDate').val());
        console.log('  ReceivedDate:', $('#ReceivedDate').val());
        
        // Ensure form is valid and ready to submit
        var form = document.getElementById('addStockForm');
        if (form) {
            // Check HTML5 validation
            if (!form.checkValidity()) {
                console.error('HTML5 validation failed');
                form.reportValidity();
                e.preventDefault();
                return false;
            }
            
            // If all validations pass, explicitly submit the form
            console.log('All validations passed, submitting form...');
            // Don't prevent default - allow form to submit naturally
            // The form will submit via normal POST
        }
        
        // Final check before allowing submission
        console.log('=== FINAL CHECK BEFORE SUBMISSION ===');
        console.log('Form will submit - all validations passed');
        console.log('Form action:', form ? form.action : 'N/A');
        console.log('Form method:', form ? form.method : 'N/A');
        
        // Don't prevent default - allow form to submit
        // Explicitly allow the form to submit by not calling preventDefault
        // Return true to allow submission
        return true;
    });
    
    // Also add a direct click handler to the submit button to catch any issues
    $(document).on('click', '#addStockForm button[type="submit"]', function(e) {
        console.log('Submit button directly clicked');
        // Don't prevent default - let the form submit handler take over
        // The form submit event will handle validation
    });
    
    // Initialize unit type and count on page load
    var initialInputMethod = $('input[name="inputMethod"]:checked').val();
    if (initialInputMethod) {
        $('#inputUnitType').val(initialInputMethod);
        if (initialInputMethod === 'pieces') {
            var pieces = parseFloat($('#quantityPieces').val()) || 0;
            $('#inputUnitCount').val(pieces);
        }
    }
    
    // Pre-select medicine if MedicineId is already set (from query string)
    var existingMedicineId = $('#MedicineId').val();
    if (existingMedicineId && existingMedicineId !== '') {
        // Fetch medicine details and set it in Select2
        $.ajax({
            url: '/Medicines/GetMedicinesList',
            data: {
                search: '',
                page: 1,
                itemsPerPage: 1000,
                sortColIndex: 0,
                sortColDir: 'asc'
            },
            success: function(response) {
                var dataArray = response.data || response.Data || [];
                var medicine = dataArray.find(function(m) {
                    var id = m.id || m.Id || '';
                    if (typeof id === 'object' && id !== null) {
                        id = id.toString ? id.toString() : (id.value || id.$oid || '');
                    }
                    return String(id) === String(existingMedicineId);
                });
                
                if (medicine) {
                    var medicineName = medicine.name || medicine.Name || 'Unknown Medicine';
                    var currentStock = medicine.currentStock || medicine.CurrentStock || 0;
                    var unitOfMeasure = (medicine.unitOfMeasure || medicine.UnitOfMeasure || '').toString();
                    var unitPrice = medicine.unitPrice || medicine.UnitPrice || null;
                    var displayText = medicineName;
                    if (currentStock > 0) {
                        displayText += ' (Current Stock: ' + currentStock + ')';
                    }
                    
                    var option = new Option(displayText, existingMedicineId, true, true);
                    $('#medicineSelect').append(option).trigger('change');
                    
                    if (currentStock > 0) {
                        $('#stockWarning').show();
                    }
                    
                    // Update cost label and pre-fill unit price based on medicine's Unit of Measure
                    // If unitPrice is not available in list data, fetch medicine details
                    if ((unitPrice == null || unitPrice === undefined || unitPrice === '') && existingMedicineId) {
                        // Fetch medicine details to get unit price
                        $.ajax({
                            url: '/Medicines/GetMedicineDetailsJson/' + existingMedicineId,
                            method: 'GET',
                            success: function(response) {
                                if (response.success && response.unitPrice != null) {
                                    var detailUnitPrice = response.unitPrice;
                                    var detailUnitOfMeasure = (response.unitOfMeasure || unitOfMeasure || '').toString();
                                    updateCostLabelAndPrice(detailUnitOfMeasure, detailUnitPrice);
                                } else {
                                    // Fallback: use what we have
                                    updateCostLabelAndPrice(unitOfMeasure, unitPrice);
                                }
                            },
                            error: function() {
                                // Fallback: use what we have
                                updateCostLabelAndPrice(unitOfMeasure, unitPrice);
                            }
                        });
                    } else {
                        updateCostLabelAndPrice(unitOfMeasure, unitPrice);
                    }
                    
                    // Hide the input method selection section - it will be auto-assigned
                    $('#inputMethodSection').hide();
                    
                    // Auto-select input method based on medicine's UnitOfMeasure
                    // Handle various enum representations: 'Box', 'box', '4', 4, 'Bottle', 'bottle', '3', 3
                    var unitMeasureLower = unitOfMeasure.toString().toLowerCase();
                    var unitMeasureNum = parseInt(unitOfMeasure) || unitOfMeasure;
                    
                    if (unitMeasureLower === 'box' || unitMeasureNum === 4 || unitMeasureNum === '4') {
                        console.log('Pre-selected medicine: Auto-selecting "By Box" input method for UnitOfMeasure:', unitOfMeasure);
                        $('#inputMethodBoxes').prop('checked', true).prop('disabled', true);
                        switchInputMethod('boxes');
                    } else if (unitMeasureLower === 'bottle' || unitMeasureNum === 3 || unitMeasureNum === '3') {
                        console.log('Pre-selected medicine: Auto-selecting "By Bottle" input method for UnitOfMeasure:', unitOfMeasure);
                        $('#inputMethodBottles').prop('checked', true).prop('disabled', true);
                        switchInputMethod('bottles');
                        
                        // Display bottle measurement info if available
                        var bottleMeasurementType = medicine.bottleMeasurementType || medicine.BottleMeasurementType || null;
                        var bottleMeasurementValue = medicine.bottleMeasurementValue || medicine.BottleMeasurementValue || null;
                        var bottleInfoText = '';
                        if (bottleMeasurementType && bottleMeasurementValue) {
                            var measureType = bottleMeasurementType.toLowerCase();
                            var measureValue = parseFloat(bottleMeasurementValue) || 0;
                            if (measureType === 'mg') {
                                bottleInfoText = 'This medicine contains ' + measureValue + 'mg per bottle (informational only)';
                            } else if (measureType === 'ml') {
                                bottleInfoText = 'This medicine contains ' + measureValue + 'ml per bottle (informational only)';
                            }
                        }
                        if (bottleInfoText) {
                            $('#bottleInfoText').text(bottleInfoText);
                            $('#bottleInfoDisplay').show();
                        } else {
                            $('#bottleInfoDisplay').hide();
                        }
                    } else {
                        // Default to boxes if unit is not box or bottle
                        console.log('Pre-selected medicine: Unknown UnitOfMeasure:', unitOfMeasure, '- defaulting to "By Box"');
                        $('#inputMethodBoxes').prop('checked', true).prop('disabled', true);
                        switchInputMethod('boxes');
                        $('#bottleInfoDisplay').hide();
                    }
                }
            }
        });
    }
    
    // Initialize input method sections on page load
    var initialInputMethod = $('input[name="inputMethod"]:checked').val();
    if (initialInputMethod) {
        // Trigger the change handler to set initial state
        console.log('Initializing with input method:', initialInputMethod);
        $('input[name="inputMethod"]:checked').trigger('change');
    } else {
        // Default to boxes if nothing is checked
        console.log('No input method selected, defaulting to boxes');
        $('#inputMethodBoxes').prop('checked', true).trigger('change');
    }
    
    // Auto-load function: Calculate total pieces on page load based on default values
    // This ensures "Number of Boxes" × "Pieces per Box" is calculated immediately
    function autoLoadCalculation() {
        console.log('autoLoadCalculation called');
        
        // Check if required elements exist
        var numberOfBoxesEl = $('#numberOfBoxes');
        var piecesPerBoxEl = $('#piecesPerBox');
        var totalPiecesFromBoxesEl = $('#totalPiecesFromBoxes');
        
        console.log('Elements check - numberOfBoxes:', numberOfBoxesEl.length, 'piecesPerBox:', piecesPerBoxEl.length, 'totalPiecesFromBoxes:', totalPiecesFromBoxesEl.length);
        
        if (numberOfBoxesEl.length === 0 || piecesPerBoxEl.length === 0 || totalPiecesFromBoxesEl.length === 0) {
            console.warn('Required elements not found, retrying in 200ms...');
            setTimeout(autoLoadCalculation, 200);
            return;
        }
        
        // Check which input method is active
        var activeInputMethod = $('input[name="inputMethod"]:checked').val();
        
        // If no input method is checked, default to boxes
        if (!activeInputMethod) {
            activeInputMethod = 'boxes';
            $('#inputMethodBoxes').prop('checked', true);
            // Ensure boxes section is visible
            $('#boxesInputSection').show();
        }
        
        console.log('Auto-load: Calculating total pieces for input method:', activeInputMethod);
        
        if (activeInputMethod === 'boxes') {
            // Get default or current values
            var numberOfBoxes = parseFloat(numberOfBoxesEl.val()) || 1;
            var piecesPerBox = parseFloat(piecesPerBoxEl.val()) || 100;
            
            // Ensure default values are set if empty
            if (!numberOfBoxesEl.val() || numberOfBoxesEl.val() === '0') {
                numberOfBoxesEl.val('1');
                numberOfBoxes = 1;
            }
            if (!piecesPerBoxEl.val() || piecesPerBoxEl.val() === '0') {
                piecesPerBoxEl.val('100');
                piecesPerBox = 100;
            }
            
            console.log('Auto-load: Values - numberOfBoxes:', numberOfBoxes, 'piecesPerBox:', piecesPerBox);
            
            // Calculate and display
            calculateTotalPiecesFromBoxes();
            
            // Verify the calculation was applied
            var calculatedValue = totalPiecesFromBoxesEl.val();
            console.log('Auto-load: Calculated', numberOfBoxes, 'boxes ×', piecesPerBox, 'pieces =', numberOfBoxes * piecesPerBox, 'total pieces. Field value:', calculatedValue);
            
            // If calculation didn't work, try again
            if (!calculatedValue || calculatedValue === '0') {
                console.warn('Calculation did not apply, retrying...');
                setTimeout(function() {
                    calculateTotalPiecesFromBoxes();
                }, 100);
            }
        } else if (activeInputMethod === 'bottles') {
            // Get default or current values
            var numberOfBottles = parseFloat($('#numberOfBottles').val()) || 1;
            var piecesPerBottle = parseFloat($('#piecesPerBottle').val()) || 1;
            
            // Ensure default values are set if empty
            if (!$('#numberOfBottles').val() || $('#numberOfBottles').val() === '0') {
                $('#numberOfBottles').val('1');
                numberOfBottles = 1;
            }
            if (!$('#piecesPerBottle').val() || $('#piecesPerBottle').val() === '0') {
                $('#piecesPerBottle').val('1');
                piecesPerBottle = 1;
            }
            
            // Calculate and display
            calculateTotalPiecesFromBottles();
            console.log('Auto-load: Calculated', numberOfBottles, 'bottles ×', piecesPerBottle, 'pieces =', numberOfBottles * piecesPerBottle, 'total pieces');
        } else {
            // Default to boxes calculation
            var numberOfBoxes = parseFloat(numberOfBoxesEl.val()) || 1;
            var piecesPerBox = parseFloat(piecesPerBoxEl.val()) || 100;
            
            if (!numberOfBoxesEl.val() || numberOfBoxesEl.val() === '0') {
                numberOfBoxesEl.val('1');
            }
            if (!piecesPerBoxEl.val() || piecesPerBoxEl.val() === '0') {
                piecesPerBoxEl.val('100');
            }
            
            calculateTotalPiecesFromBoxes();
        }
        
        // Initialize summary on page load
        updateSummary();
    }
    
    // Execute auto-load calculation with delays to ensure DOM is ready
    // Try multiple times to ensure it works
    console.log('Setting up auto-load calculation timers...');
    
    setTimeout(function() {
        console.log('Auto-load timer 1 (200ms) triggered');
        autoLoadCalculation();
    }, 200);
    
    setTimeout(function() {
        console.log('Auto-load timer 2 (500ms) triggered');
        autoLoadCalculation();
    }, 500);
    
    setTimeout(function() {
        console.log('Auto-load timer 3 (1000ms) triggered');
        autoLoadCalculation();
    }, 1000);
    
    // Also execute immediately if DOM is already ready
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        console.log('DOM already ready, executing auto-load immediately');
        setTimeout(function() {
            console.log('Auto-load (immediate) triggered');
            autoLoadCalculation();
        }, 100);
    } else {
        console.log('DOM not ready yet, state:', document.readyState);
        // Wait for DOM to be ready
        $(document).ready(function() {
            console.log('Document ready event fired, executing auto-load');
            setTimeout(function() {
                autoLoadCalculation();
            }, 100);
        });
    }
    
    console.log('Auto-load calculation setup complete');
});
