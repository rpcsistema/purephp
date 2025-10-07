<footer class="footer-modern container">
  <div class="d-flex justify-content-between align-items-center">
    <small>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(app_name()); ?>. Todos os direitos reservados.</small>
    <small>Design moderno com paleta verde/acento azul.</small>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Inicializa tooltips Bootstrap para elementos com atributo title
  (function(){
    if (window.bootstrap && document){
      const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
      tooltipTriggerList.forEach(function (el) {
        new bootstrap.Tooltip(el);
      });
    }
  })();
</script>