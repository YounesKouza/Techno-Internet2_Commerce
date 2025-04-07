            </main>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- Notre fichier JavaScript pour l'interface admin -->
    <script src="/Exos/Techno-internet2_commerce/admin/public/js/fonction.js"></script>
    
    <script>
        // Initialisation des DataTables
        $(document).ready(function() {
            // Si une table avec la classe 'datatable' existe, l'initialiser
            if ($.fn.DataTable) {
                $('.datatable').DataTable({
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json"
                    },
                    responsive: true,
                    "pageLength": 10,
                    "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Tous"]]
                });
            }
            
            // Fermeture automatique des alertes après 5 secondes
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        });
    </script>
    
    <!-- Scripts additionnels spécifiques à la page courante -->
    <?php if (isset($pageScripts)) { echo $pageScripts; } ?>
</body>
</html> 