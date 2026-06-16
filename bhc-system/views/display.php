<?php include 'layout/header.php'; ?>

<div class="container-fluid text-center mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h1 class="display-1 fw-bold text-uppercase">Now Serving</h1>
            <hr class="my-4">
            
            <!-- This card will display the current queue number -->
            <div class="card shadow-lg border-primary">
                <div class="card-body py-5">
                    <div class="queue-display" style="font-size: 8rem; font-weight: 800; color: #0d6efd;">
                        BHC-000
                    </div>
                </div>
            </div>
            
            <p class="lead mt-4 fs-3">Please proceed to the <strong>Consultation Room</strong></p>
            
            <div class="mt-5 text-muted">
                <p>Waiting Area Dashboard | <?php echo date('F j, Y'); ?></p>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>