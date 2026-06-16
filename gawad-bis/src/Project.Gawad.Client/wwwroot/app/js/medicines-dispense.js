$(document).ready(function () {
    // Handle recipient type toggle (Resident vs Non-Resident with/without Prescription)
    $('input[name="recipientType"]').on('change', function() {
        var recipientType = $(this).val();
        var residentSection = $('#residentRecipientSection');
        var nonResidentSection = $('#nonResidentRecipientSection');
        var prescriptionInput = $('#prescriptionInput');
        var prescriptionHelpText = $('#prescriptionHelpText');
        var nonResidentNameInput = $('#nonResidentName');
        
        if (recipientType === 'resident') {
            // Show resident section, hide non-resident section
            residentSection.show();
            nonResidentSection.hide();
            
            // Clear non-resident fields
            nonResidentNameInput.val('');
            prescriptionInput.val('');
            prescriptionInput.removeAttr('required');
            
            // Clear hidden fields if resident select is empty
            if (!$('#recipientSelect').val()) {
                $('#RecipientPersonId').val('');
                $('#RecipientName').val('');
            }
        } else if (recipientType === 'nonresident') {
            // Show non-resident section with prescription, hide resident section
            residentSection.hide();
            nonResidentSection.show();
            
            // Clear resident fields
            $('#recipientSelect').val(null).trigger('change');
            $('#RecipientPersonId').val('');
            $('#RecipientName').val('');
            
            // Make prescription required for non-residents with prescription
            prescriptionInput.attr('required', 'required');
            prescriptionHelpText.text('Required for non-residents with prescription');
        } else if (recipientType === 'nonresidentnoprescription') {
            // Show non-resident section without prescription, hide resident section
            residentSection.hide();
            nonResidentSection.show();
            
            // Clear resident fields
            $('#recipientSelect').val(null).trigger('change');
            $('#RecipientPersonId').val('');
            $('#RecipientName').val('');
            
            // Make prescription optional for non-residents without prescription
            prescriptionInput.removeAttr('required');
            prescriptionInput.val(''); // Clear prescription field
            prescriptionHelpText.text('Optional: Enter prescription if available');
        }
    });

    // Update RecipientName when non-resident name is entered
    $('#nonResidentName').on('input', function() {
        var nonResidentName = $(this).val().trim();
        if (nonResidentName) {
            $('#RecipientName').val(nonResidentName);
            $('#RecipientPersonId').val(''); // Clear person ID for non-residents
        } else {
            $('#RecipientName').val('');
        }
    });

    // Initialize Select2 for resident selection
    $('#recipientSelect').select2({
        theme: 'bootstrap4',
        placeholder: 'Type to search for a resident...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('body'),
        language: {
            searching: function() {
                return 'Searching residents...';
            },
            noResults: function() {
                return 'No residents found';
            }
        },
        ajax: {
            url: '/Residents/GetResidentsList',
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
                
                // Map and deduplicate results by personId
                var resultsMap = new Map();
                var seenPersonIds = new Set();
                var seenNames = new Set();
                
                dataArray.forEach(function (resident) {
                    // Handle both camelCase and PascalCase property names
                    var residentId = resident.id || resident.Id || '';
                    var residentName = resident.name || resident.Name || '';
                    var personId = resident.personId || resident.PersonId || '';
                    
                    // Skip if no name
                    if (!residentName || residentName.trim() === '') {
                        return;
                    }
                    
                    // Normalize names and IDs
                    residentName = String(residentName).trim();
                    
                    // If IDs are objects (ObjectId), get the string value
                    if (typeof residentId === 'object' && residentId !== null) {
                        residentId = residentId.toString ? residentId.toString() : (residentId.value || residentId.$oid || '');
                    }
                    if (typeof personId === 'object' && personId !== null) {
                        personId = personId.toString ? personId.toString() : (personId.value || personId.$oid || '');
                    }
                    
                    // Normalize to strings
                    var personIdStr = String(personId).trim();
                    var residentNameLower = residentName.toLowerCase();
                    
                    // Use personId as primary deduplication key, fallback to name if personId is missing
                    var uniqueKey = '';
                    var displayId = '';
                    
                    if (personIdStr && personIdStr !== '' && personIdStr !== 'undefined' && personIdStr !== 'null') {
                        uniqueKey = personIdStr;
                        displayId = personIdStr;
                    } else {
                        // Fallback: use name for deduplication if personId is not available
                        uniqueKey = residentNameLower;
                        displayId = String(residentId).trim() || residentNameLower;
                    }
                    
                    // Check if we've already seen this personId or name
                    var isDuplicate = false;
                    if (personIdStr && personIdStr !== '' && personIdStr !== 'undefined' && personIdStr !== 'null') {
                        if (seenPersonIds.has(personIdStr)) {
                            isDuplicate = true;
                        } else {
                            seenPersonIds.add(personIdStr);
                        }
                    } else {
                        // Check by name if personId is not available
                        if (seenNames.has(residentNameLower)) {
                            isDuplicate = true;
                        } else {
                            seenNames.add(residentNameLower);
                        }
                    }
                    
                    // Only add if not duplicate
                    if (!isDuplicate && uniqueKey) {
                        resultsMap.set(uniqueKey, {
                            id: displayId,
                            text: residentName,
                            data: resident
                        });
                    }
                });
                
                // Convert map to array and sort by name for consistent display
                var results = Array.from(resultsMap.values());
                results.sort(function(a, b) {
                    return a.text.localeCompare(b.text);
                });
                
                return {
                    results: results,
                    pagination: {
                        more: hasMore
                    }
                };
            },
            error: function(xhr, status, error) {
                console.error('Error loading residents:', error);
            },
            cache: false
        },
        minimumInputLength: 1
    });

    // When a resident is selected, update the hidden fields
    $('#recipientSelect').on('select2:select', function(e) {
        var selectedData = e.params.data;
        var personId = selectedData.id;
        var recipientName = selectedData.text;
        
        // Update hidden fields
        $('#RecipientPersonId').val(personId);
        $('#RecipientName').val(recipientName);
    });

    // When selection is cleared, clear the hidden fields
    $('#recipientSelect').on('select2:clear', function(e) {
        $('#RecipientPersonId').val('');
        $('#RecipientName').val('');
        updateAllocationWarning();
    });

    // Check medicine warnings when medicine ID is set
    var medicineIdInput = $('#medicineIdInput');
    var quantityInput = $('#Quantity');
    
    // Check warnings when medicine ID changes
    medicineIdInput.on('change input', function() {
        checkMedicineWarnings();
    });

    // Also check allocation when recipient or quantity changes (only for residents)
    $('#recipientSelect').on('select2:select', function(e) {
        var recipientType = $('input[name="recipientType"]:checked').val();
        if (recipientType === 'resident') {
            updateAllocationWarning();
        }
    });

    quantityInput.on('input', function() {
        var recipientType = $('input[name="recipientType"]:checked').val();
        if (recipientType === 'resident') {
            updateAllocationWarning();
        }
    });

    // Check medicine warnings (low stock, allocation limits)
    function checkMedicineWarnings() {
        var medicineId = medicineIdInput.val();
        if (!medicineId || medicineId.trim() === '') {
            $('#medicineWarnings').hide();
            return;
        }

        $.ajax({
            url: '/Medicines/Details/' + medicineId,
            method: 'GET',
            success: function(response) {
                // Parse HTML response to extract medicine details
                // Or use a JSON endpoint if available
                // For now, let's use the existing endpoint structure
            },
            error: function() {
                // Try alternative approach - fetch via API if available
            }
        });

        // Use GetMedicinesList to find medicine details
        $.ajax({
            url: '/Medicines/GetMedicinesList',
            method: 'GET',
            data: {
                search: '',
                page: 1,
                itemsPerPage: 1000
            },
            success: function(response) {
                var dataArray = response.data || response.Data || [];
                var medicine = dataArray.find(function(m) {
                    var id = m.id || m.Id || '';
                    if (typeof id === 'object' && id !== null) {
                        id = id.toString ? id.toString() : (id.value || id.$oid || '');
                    }
                    return String(id) === String(medicineId);
                });

                if (medicine) {
                    showMedicineWarnings(medicine);
                }
            }
        });
    }

    // Show medicine warnings
    function showMedicineWarnings(medicine) {
        var warningsDiv = $('#medicineWarnings');
        var lowStockWarning = $('#lowStockWarning');
        var allocationWarning = $('#allocationLimitWarning');
        var lowStockMessage = $('#lowStockMessage');
        var allocationMessage = $('#allocationLimitMessage');

        // Reset warnings
        warningsDiv.hide();
        lowStockWarning.hide();
        allocationWarning.hide();

        var hasWarnings = false;

        // Check for low stock
        var currentStock = medicine.currentStock || medicine.CurrentStock || 0;
        var minimumStock = medicine.minimumStockLevel || medicine.MinimumStockLevel || 0;
        var isLowStock = medicine.isLowStock || medicine.IsLowStock || false;

        if (isLowStock || currentStock <= minimumStock) {
            hasWarnings = true;
            var remainingStock = Math.max(0, currentStock);
            lowStockMessage.text('Current stock: ' + remainingStock + 
                ' (Minimum: ' + minimumStock + '). Please replenish stock soon.');
            lowStockWarning.show();
        }

        // Check for allocation limits
        var isLimitedSupply = medicine.isLimitedSupply || medicine.IsLimitedSupply || false;
        var allocationPeriod = medicine.allocationPeriod || medicine.AllocationPeriod || 0;
        var maxQuantityPerPeriod = medicine.maxQuantityPerPeriod || medicine.MaxQuantityPerPeriod || null;

        if (isLimitedSupply && allocationPeriod && allocationPeriod !== 0 && maxQuantityPerPeriod) {
            hasWarnings = true;
            var periodText = allocationPeriod === 1 ? 'week' : (allocationPeriod === 2 ? 'month' : 'period');
            allocationMessage.text('This medicine has allocation limits. Maximum ' + 
                maxQuantityPerPeriod + ' per ' + periodText + ' per resident.');
            allocationWarning.show();
        }

        if (hasWarnings) {
            warningsDiv.show();
        }
    }

    // Update allocation warning based on selected resident and quantity
    function updateAllocationWarning() {
        var medicineId = medicineIdInput.val();
        var recipientId = $('#RecipientPersonId').val();
        var quantity = parseFloat(quantityInput.val()) || 0;
        var recipientType = $('input[name="recipientType"]:checked').val();

        // Only check allocation for residents
        if (recipientType !== 'resident' || !medicineId || !recipientId || quantity <= 0) {
            return;
        }

        // Note: Full allocation checking is done server-side during form submission
        // This is just for display purposes
    }

    // Get stock information from the page (set by Razor view)
    var currentStock = (typeof medicineStockInfo !== 'undefined') ? medicineStockInfo.currentStock : 0;
    var minimumStock = (typeof medicineStockInfo !== 'undefined') ? medicineStockInfo.minimumStock : 0;
    var availableForDispense = Math.max(0, currentStock - minimumStock);
    
    // Update available stock display (considering minimum stock level)
    function updateAvailableStock() {
        var quantity = parseFloat($('#Quantity').val()) || 0;
        var remaining = currentStock - quantity;
        
        // Update available stock display (considering minimum stock level)
        var availableAfterDispense = Math.max(0, remaining - minimumStock);
        $('#availableStock').text(availableAfterDispense.toLocaleString('en-US', {maximumFractionDigits: 0}));
    }
    
    // Validate quantity input (per piece dispense)
    var quantityInput = $('#Quantity');
    var quantityValidationMessage = $('#quantityValidationMessage');
    
    if (quantityInput.length > 0) {
        // Ensure quantity is a whole number (pieces only)
        quantityInput.on('input', function() {
            var value = parseFloat($(this).val()) || 0;
            var maxValue = currentStock;
            
            // Round to nearest whole number
            if (value !== Math.floor(value)) {
                $(this).val(Math.floor(value));
                value = Math.floor(value);
            }
            
            // Validate against current stock
            if (value > maxValue) {
                quantityValidationMessage.text('Quantity cannot exceed current stock (' + maxValue.toLocaleString() + ' pieces).').show();
                $(this).val(maxValue);
                value = maxValue;
            } else if (value < 1) {
                quantityValidationMessage.text('Quantity must be at least 1 piece.').show();
            } else {
                quantityValidationMessage.hide();
            }
            
            // Update available stock display
            updateAvailableStock();
            
            // Update allocation warning when quantity changes
            updateAllocationWarning();
        });
        
        // Initialize available stock display on page load
        updateAvailableStock();
        
        // Validate before form submission
        $('form').on('submit', function(e) {
            var quantity = parseFloat(quantityInput.val()) || 0;
            var recipientType = $('input[name="recipientType"]:checked').val();
            
            if (quantity <= 0) {
                e.preventDefault();
                alert('Please enter a valid quantity (must be at least 1 piece).');
                quantityInput.focus();
                return false;
            }
            
            // Ensure quantity is a whole number
            if (quantity !== Math.floor(quantity)) {
                e.preventDefault();
                alert('Quantity must be a whole number (pieces only).');
                quantityInput.focus();
                return false;
            }
            
            // Validate against current stock
            if (quantity > currentStock) {
                e.preventDefault();
                alert('Quantity cannot exceed current stock (' + currentStock.toLocaleString() + ' pieces).');
                quantityInput.focus();
                return false;
            }

            // Validate recipient based on type
            if (recipientType === 'resident') {
                // For residents, ensure a resident is selected
                var recipientPersonId = $('#RecipientPersonId').val();
                var recipientName = $('#RecipientName').val();
                if (!recipientPersonId || !recipientName) {
                    e.preventDefault();
                    alert('Please select a resident from the list.');
                    $('#recipientSelect').focus();
                    return false;
                }
            } else if (recipientType === 'nonresident') {
                // For non-residents with prescription, ensure name and prescription are provided
                var nonResidentName = $('#nonResidentName').val().trim();
                var prescription = $('#prescriptionInput').val().trim();
                
                if (!nonResidentName) {
                    e.preventDefault();
                    alert('Please enter the recipient name for non-resident.');
                    $('#nonResidentName').focus();
                    return false;
                }
                
                if (!prescription) {
                    e.preventDefault();
                    alert('Prescription number/details is required for non-residents with prescription.');
                    $('#prescriptionInput').focus();
                    return false;
                }
                
                // Update hidden RecipientName field
                $('#RecipientName').val(nonResidentName);
                $('#RecipientPersonId').val(''); // Ensure person ID is empty for non-residents
            } else if (recipientType === 'nonresidentnoprescription') {
                // For non-residents without prescription, only name is required
                var nonResidentName = $('#nonResidentName').val().trim();
                
                if (!nonResidentName) {
                    e.preventDefault();
                    alert('Please enter the recipient name for non-resident.');
                    $('#nonResidentName').focus();
                    return false;
                }
                
                // Update hidden RecipientName field
                $('#RecipientName').val(nonResidentName);
                $('#RecipientPersonId').val(''); // Ensure person ID is empty for non-residents
            }
        });
    }
});

