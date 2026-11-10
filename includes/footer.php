        </div>
    </main>

    <!-- Pie de Página Institucional -->
    <footer class="app-footer">
        <div class="container-fluid px-lg-4">
            <div class="row gy-3 align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-1 fw-bold text-light">
                        SIRCE &bull; Sistema Integrado de Radicación, Ruteo y Calidad de Encuestas
                    </p>
                    <p class="mb-0 text-muted small">
                        Proyecto de Desarrollo de Software II &bull; Universidad del Valle &bull; John Robert Causimanse
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <span class="badge bg-secondary-subtle text-light border border-secondary small me-2">
                        <i class="bi bi-shield-check me-1"></i> Control de SLA: 15 Días Calendario
                    </span>
                    <span class="badge bg-secondary-subtle text-light border border-secondary small">
                        <i class="bi bi-geo-alt me-1"></i> Cobertura Cali Urbana & Rural
                    </span>
                    <div class="mt-2 small text-muted">
                        PHP <?= PHP_VERSION ?> &bull; MariaDB 10.4 &bull; Leaflet &bull; Chart.js &bull; FPDF
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Chart.js para Dashboard -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Script principal de la aplicación -->
    <script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
