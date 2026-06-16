<?php if (isset($useSidebar) && $useSidebar): ?>
          <?php require __DIR__ . '/partials/site-footer.php'; ?>
          </div>
        </div>
      </div>
    <?php else: ?>
      </div>
      </main>
      <?php require __DIR__ . '/partials/site-footer.php'; ?>
    <?php endif; ?>
    <?php
      $footerPath = (string) ($currentPath ?? '');
      $hideGoToTop = str_starts_with($footerPath, '/display') || str_starts_with($footerPath, '/ticket');
    ?>
    <?php if (!$hideGoToTop): ?>
      <button type="button" class="go-to-top" id="goToTopBtn" aria-label="Back to top" title="Back to top">
        <svg class="go-to-top-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M12 19V6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
          <path d="M7 11l5-5 5 5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="go-to-top-label">Top</span>
      </button>
    <?php endif; ?>
    <div id="tooltip-layer"></div>
    <?php if (!empty($flashOpenUrl)): ?>
    <script>
      (function () {
        var url = <?= json_encode($flashOpenUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) ?>;
        if (url) window.open(url, '_blank', 'noopener,noreferrer');
      })();
    </script>
    <?php endif; ?>
    <script src="<?= htmlspecialchars($uTooltip ?? url_path($basePath ?? '', '/assets/tooltip.js')) ?>"></script>
    <script>
      (function () {
        document.addEventListener('click', function (e) {
          document.querySelectorAll('details.nav-dropdown[open], details.appt-menu[open]').forEach(function (menu) {
            if (!menu.contains(e.target)) {
              menu.open = false;
            }
          });
        });
      })();
    </script>
    <?php if (!$hideGoToTop): ?>
    <script>
      (function () {
        var btn = document.getElementById('goToTopBtn');
        if (!btn) return;

        var threshold = 320;
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function toggleVisibility() {
          var show = window.scrollY > threshold;
          btn.classList.toggle('is-visible', show);
          btn.setAttribute('aria-hidden', show ? 'false' : 'true');
          btn.tabIndex = show ? 0 : -1;
        }

        btn.addEventListener('click', function () {
          window.scrollTo({
            top: 0,
            behavior: reduceMotion ? 'auto' : 'smooth'
          });
        });

        window.addEventListener('scroll', toggleVisibility, { passive: true });
        toggleVisibility();
      })();
    </script>
    <?php endif; ?>
    <?php if (isset($useSidebar) && $useSidebar): ?>
    <script>
      (function () {
        document.querySelectorAll('.sidebar .side-link').forEach(function (link) {
          link.addEventListener('click', function () {
            document.body.classList.remove('sidebar-open');
          });
        });
      })();
    </script>
    <?php endif; ?>
  </body>
</html>
