<style>
  .loader-overlay {
    position: fixed;
    inset: 0;
    background: rgba(255,255,255,.6);
    backdrop-filter: blur(2px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    visibility: hidden;
    opacity: 0;
    transition: opacity .2s ease, visibility .2s;
  }

  .loader-overlay.show {
    visibility: visible;
    opacity: 1;
  }

  .loader-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #0E2B51; 
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin .8s linear infinite;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }
</style>

<div id="loaderOverlay" class="loader-overlay">
  <div class="loader-spinner"></div>
</div>
