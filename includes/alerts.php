<?php
$flash = getFlash();
if ($flash):
    $type = $flash['type'] ?? 'info';
    $message = $flash['message'] ?? '';
    
    $icon = 'fa-circle-info';
    if ($type === 'success') $icon = 'fa-circle-check';
    if ($type === 'danger')  $icon = 'fa-triangle-exclamation';
    if ($type === 'warning') $icon = 'fa-circle-exclamation';
?>
<div class="container mt-3">
    <div class="alert alert-<?php echo htmlspecialchars($type); ?> alert-dismissible fade show shadow-sm d-flex align-items-center gap-2" role="alert">
        <i class="fa-solid <?php echo $icon; ?> fs-5"></i>
        <div class="flex-grow-1">
            <?php echo $message; ?>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php endif; ?>
