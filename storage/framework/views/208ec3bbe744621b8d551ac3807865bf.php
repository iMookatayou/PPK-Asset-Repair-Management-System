<footer class="footer-hero mt-auto text-center text-light py-3">
  <div class="container">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <div class="fw-semibold small">
        © <?php echo e(date('Y')); ?> Phrapokklao Hospital — Information Technology Group
      </div>
      <div class="small opacity-75">
        Asset Repair Management System • Build <?php echo e(app()->version()); ?>

      </div>
    </div>
  </div>
</footer>

<style>
  .footer-hero {
    background-color: #0F2D5C; 
    color: #EAF2FF;
    font-family: 'Sarabun', system-ui, sans-serif;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.2);
    border-top: 1px solid rgba(255,255,255,.12);
  }
  .footer-hero a {
    color: #EAF2FF;
    text-decoration: none;
  }
  .footer-hero a:hover {
    color: #ffffff;
    text-decoration: underline;
  }
</style>
<?php /**PATH /var/www/html/resources/views/components/footer.blade.php ENDPATH**/ ?>