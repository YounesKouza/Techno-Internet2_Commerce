$(document).ready(function() {
    // ======================================
    // Fonctions générales
    // ======================================
    
    // Fonction pour mettre à jour le compteur du panier
    function updateCartCount(count) {
        const cartBadge = $('#cart-badge');
        if (cartBadge.length) {
            cartBadge.text(count);
            if (count > 0) {
                cartBadge.show();
            } else {
                cartBadge.hide();
            }
        } else {
            console.warn("Élément #cart-badge non trouvé dans le DOM");
        }
    }
    
    // Fonction pour afficher une notification
    function showNotification(message, type = 'success') {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const notification = $(`<div class="alert ${alertClass} notification">${message}</div>`);
        $('body').append(notification);
        
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // ======================================
    // Actions du panier
    // ======================================
    
    // Ajouter un produit au panier
    $('.add-to-cart').click(function(e) {
        e.preventDefault();
        console.log('Ajout au panier cliqué pour produit ID:', $(this).data('product-id'));
        
        const productId = $(this).data('product-id');
        const quantity = parseInt($('#quantity-' + productId).val() || 1);
        
        $.ajax({
            type: 'POST',
            url: 'admin/src/php/ajax/add_to_cart.php',
            data: {
                product_id: productId,
                quantity: quantity
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message);
                    updateCartCount(response.cart_count);
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', status, error);
                showNotification('Erreur de communication avec le serveur', 'error');
            }
        });
    });
    
    // Supprimer un produit du panier
    $('.remove-from-cart, .remove-item').click(function(e) {
        e.preventDefault();
        
        const productId = $(this).data('product-id');
        
        $.ajax({
            type: 'POST',
            url: 'admin/src/php/ajax/remove_from_cart.php',
            data: {
                product_id: productId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message);
                    updateCartCount(response.cart_count);
                    
                    // Mettre à jour le DOM en supprimant l'élément
                    $('#cart-item-' + productId).fadeOut(300, function() {
                        $(this).remove();
                        updateCartTotal();
                        
                        // Si le panier est maintenant vide, afficher le message approprié
                        if ($('.cart-table tbody tr').length === 0) {
                            $('.cart-summary').addClass('d-none');
                            $('.cart-actions').addClass('d-none');
                            $('.empty-cart-message').removeClass('d-none');
                        }
                    });
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function() {
                showNotification('Erreur de communication avec le serveur', 'error');
            }
        });
    });
    
    // Mettre à jour la quantité d'un produit
    $('.update-quantity').change(function() {
        const productId = $(this).data('product-id');
        const quantity = parseInt($(this).val());
        
        $.ajax({
            type: 'POST',
            url: 'admin/src/php/ajax/cart_actions.php',
            data: {
                action: 'update',
                product_id: productId,
                quantity: quantity
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message);
                    updateCartCount(response.cart_count);
                    
                    // Si la quantité est 0, supprimer l'élément
                    if (quantity <= 0) {
                        $('#cart-item-' + productId).fadeOut(300, function() {
                            $(this).remove();
                        });
                    }
                    
                    // Mettre à jour le sous-total et le total
                    updateCartTotal();
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function() {
                showNotification('Erreur de communication avec le serveur', 'error');
            }
        });
    });
    
    // Gestion des boutons +/- pour la quantité
    $('.update-quantity[data-action]').click(function(e) {
        e.preventDefault();
        
        const productId = $(this).data('product-id');
        const action = $(this).data('action');
        const quantityInput = $(this).closest('td').find('.quantity-input');
        let currentQty = parseInt(quantityInput.val());
        
        // Augmenter ou diminuer selon l'action
        if (action === 'increase') {
            currentQty += 1;
        } else if (action === 'decrease' && currentQty > 1) {
            currentQty -= 1;
        } else if (action === 'decrease' && currentQty === 1) {
            // Confirmer avant de supprimer
            if (confirm('Voulez-vous supprimer cet article du panier ?')) {
                currentQty = 0;
            } else {
                return; // Annuler l'opération
            }
        }
        
        // Mettre à jour l'affichage
        quantityInput.val(currentQty);
        
        // Appeler l'API pour mettre à jour le panier
        $.ajax({
            type: 'POST',
            url: 'admin/src/php/ajax/cart_actions.php',
            data: {
                action: 'update',
                product_id: productId,
                quantity: currentQty
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message);
                    updateCartCount(response.cart_count);
                    
                    // Si la quantité est 0, supprimer l'élément
                    if (currentQty <= 0) {
                        $('#cart-item-' + productId).fadeOut(300, function() {
                            $(this).remove();
                            
                            // Vérifier si le panier est vide
                            if ($('.cart-table tbody tr').length === 0) {
                                $('.cart-summary').addClass('d-none');
                                $('.cart-actions').addClass('d-none');
                                $('.empty-cart-message').removeClass('d-none');
                            } else {
                                // Mettre à jour le total global si le panier n'est pas vide
                                updateCartTotal();
                            }
                        });
                    } else {
                        // Mettre à jour le sous-total de l'article
                        const priceText = $('#cart-item-' + productId + ' .price-col span').text();
                        const price = parseFloat(priceText.replace(',', '.').replace(' ', ''));
                        const newSubtotal = (price * currentQty).toFixed(2).replace('.', ',');
                        $('#cart-item-' + productId + ' .subtotal-col span').text(newSubtotal);
                        
                        // Recalculer le total global
                        let cartTotal = 0;
                        $('.subtotal-col span').each(function() {
                            const subtotal = parseFloat($(this).text().replace(',', '.').replace(' ', ''));
                            if (!isNaN(subtotal)) {
                                cartTotal += subtotal;
                            }
                        });
                        
                        // Mettre à jour l'affichage du total
                        $('#cart-total').text(cartTotal.toFixed(2).replace('.', ',') + ' €');
                    }
                } else {
                    showNotification(response.message, 'error');
                    // Restaurer la quantité précédente en cas d'erreur
                    quantityInput.val(currentQty - (action === 'increase' ? 1 : -1));
                }
            },
            error: function() {
                showNotification('Erreur de communication avec le serveur', 'error');
                // Restaurer la quantité précédente en cas d'erreur
                quantityInput.val(currentQty - (action === 'increase' ? 1 : -1));
            }
        });
    });
    
    // Vider le panier
    $('.clear-cart').click(function(e) {
        e.preventDefault();
        
        if (confirm('Êtes-vous sûr de vouloir vider votre panier ?')) {
            $.ajax({
                type: 'POST',
                url: 'admin/src/php/ajax/cart_actions.php',
                data: {
                    action: 'clear'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification('Votre panier a été vidé');
                        updateCartCount(0);
                        $('.cart-items').empty();
                        $('.cart-total').text('0.00 €');
                    } else {
                        showNotification(response.message, 'error');
                    }
                },
                error: function() {
                    showNotification('Erreur de communication avec le serveur', 'error');
                }
            });
        }
    });
    
    // Fonction pour mettre à jour le total du panier
    function updateCartTotal() {
        $.ajax({
            type: 'POST',
            url: 'admin/src/php/ajax/cart_actions.php',
            data: {
                action: 'get_cart'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Mettre à jour le total
                    const cartTotal = $('#cart-total');
                    if (cartTotal.length) {
                        cartTotal.text(response.total.toFixed(2).replace('.', ',') + ' €');
                    }
                    
                    // Mettre à jour le compteur
                    updateCartCount(response.items.length);
                    
                    // Si le panier est vide, afficher un message
                    if (response.items.length === 0) {
                        $('.cart-summary').addClass('d-none');
                        $('.cart-actions').addClass('d-none');
                        $('.empty-cart-message').removeClass('d-none');
                    }
                } else {
                    console.error('Erreur lors de la récupération du panier:', response.message);
                }
            },
            error: function() {
                console.error('Erreur lors de la communication avec le serveur');
            }
        });
    }
    
    // ======================================
    // Fonctions spécifiques aux pages admin
    // ======================================
    
    // Fonction pour basculer la suppression d'une image (page update_meuble.php)
    window.toggleDeleteImage = function(imageId) {
        const checkbox = document.getElementById('delete_image_' + imageId);
        const button = checkbox.parentElement.querySelector('.btn-delete');
        
        if (checkbox.checked) {
            checkbox.checked = false;
            button.classList.remove('btn-danger');
            button.classList.add('btn-outline-danger');
            button.innerHTML = '<i class="fas fa-trash-alt text-danger"></i>';
        } else {
            checkbox.checked = true;
            button.classList.remove('btn-outline-danger');
            button.classList.add('btn-danger');
            button.innerHTML = '<i class="fas fa-trash-alt text-white"></i>';
        }
    };
    
    // Filtres pour la page images.php
    if (document.getElementById('show-all')) {
        document.getElementById('show-all').addEventListener('click', function() {
            document.querySelectorAll('#images-table tbody tr').forEach(row => {
                row.style.display = '';
            });
        });
        
        document.getElementById('show-linked').addEventListener('click', function() {
            document.querySelectorAll('#images-table tbody tr').forEach(row => {
                if (row.classList.contains('linked')) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        document.getElementById('show-unlinked').addEventListener('click', function() {
            document.querySelectorAll('#images-table tbody tr').forEach(row => {
                if (row.classList.contains('unlinked')) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Initialisation du modal pour lier des images
        document.querySelectorAll('[data-bs-target="#linkModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const imageId = this.getAttribute('data-id');
                const currentProductId = this.getAttribute('data-current-product');
                
                document.getElementById('modal-image-id').value = imageId;
                
                const productSelect = document.getElementById('product_id');
                if (currentProductId) {
                    productSelect.value = currentProductId;
                } else {
                    productSelect.selectedIndex = 0;
                }
            });
        });
    }
    
    // Code pour la page statistiques
    if ($('#salesChart').length && window.chartData) {
        // Chart.js configuration globale
        Chart.defaults.font.family = "'Poppins', 'Helvetica', 'Arial', sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = '#555';
        
        // Graphique des ventes mensuelles
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: window.chartData.months_labels,
                datasets: [{
                    label: 'Chiffre d\'affaires (€)',
                    data: window.chartData.sales_values,
                    backgroundColor: 'rgba(13, 110, 253, 0.2)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, " ") + ' €';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, " ") + ' €';
                            }
                        }
                    }
                }
            }
        });
        
        // Graphique des ventes par catégorie
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: window.chartData.category_names,
                datasets: [{
                    data: window.chartData.category_sales,
                    backgroundColor: [
                        'rgba(13, 110, 253, 0.7)',   // Bleu
                        'rgba(25, 135, 84, 0.7)',    // Vert
                        'rgba(220, 53, 69, 0.7)',    // Rouge
                        'rgba(255, 193, 7, 0.7)',    // Jaune
                        'rgba(111, 66, 193, 0.7)',   // Violet
                        'rgba(23, 162, 184, 0.7)',   // Cyan
                        'rgba(102, 16, 242, 0.7)',   // Indigo
                        'rgba(253, 126, 20, 0.7)',   // Orange
                        'rgba(32, 201, 151, 0.7)',   // Teal
                        'rgba(108, 117, 125, 0.7)',  // Gris
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return label + ': ' + value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, " ") + ' € (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
        
        // Graphique des nouveaux utilisateurs
        const usersCtx = document.getElementById('usersChart').getContext('2d');
        const usersChart = new Chart(usersCtx, {
            type: 'bar',
            data: {
                labels: window.chartData.months_labels,
                datasets: [{
                    label: 'Nouveaux clients',
                    data: window.chartData.users_values,
                    backgroundColor: 'rgba(23, 162, 184, 0.7)',
                    borderColor: 'rgba(23, 162, 184, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        }
                    }
                }
            }
        });
        
        // Graphique des statuts de commandes
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: window.chartData.status_labels,
                datasets: [{
                    data: window.chartData.status_counts,
                    backgroundColor: [
                        'rgba(25, 135, 84, 0.7)',   // Vert (completed)
                        'rgba(13, 110, 253, 0.7)',  // Bleu (processing)
                        'rgba(255, 193, 7, 0.7)',   // Jaune (pending)
                        'rgba(220, 53, 69, 0.7)',   // Rouge (cancelled)
                        'rgba(108, 117, 125, 0.7)', // Gris (autres)
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Initialiser DataTable pour les tableaux de données
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
            },
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Tous"]]
        });
    }
    
    // Prévisualisation d'image pour les formulaires d'upload
    $('#image_principale, #images').on('change', function() {
        const inputId = $(this).attr('id');
        
        // Prévisualisation pour l'image principale
        if (inputId === 'image_principale' && this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#image_preview').attr('src', e.target.result).show();
            }
            reader.readAsDataURL(this.files[0]);
        }
        
        // Prévisualisation pour les images additionnelles
        if (inputId === 'images' && this.files && this.files.length > 0) {
            const previewContainer = $('#additional_images_preview');
            previewContainer.empty();
            
            for (let i = 0; i < this.files.length; i++) {
                const file = this.files[i];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const previewElement = $(`
                        <div class="col-md-3 mb-2">
                            <img src="${e.target.result}" alt="Aperçu" class="img-thumbnail" style="height: 100px; object-fit: cover;">
                        </div>
                    `);
                    previewContainer.append(previewElement);
                };
                
                reader.readAsDataURL(file);
            }
        }
    });
    
    // Toggle sidebar mobile
    $('.sidebar-toggle').click(function() {
        $('.sidebar').toggleClass('active');
        $('.sidebar-overlay').toggleClass('active');
    });
    
    $('.sidebar-overlay').click(function() {
        $('.sidebar').removeClass('active');
        $(this).removeClass('active');
    });
    
    // Confirmation pour les actions de suppression
    $('.btn-delete').click(function(e) {
        if (!$(this).attr('onclick') && !confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
            e.preventDefault();
        }
    });
    
    // Effet de la barre de navigation au défilement (déplacé depuis header.php)
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
    
    // Ajout automatique de la classe admin-interface au body (déplacé depuis all_includes.php)
    if (window.location.pathname.includes('/admin/')) {
        document.body.classList.add("admin-interface");
        console.log("Added admin-interface class to body");
    }
    
    // Script pour afficher/masquer le mot de passe dans le formulaire de connexion (déplacé depuis login.php)
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    if (togglePasswordButtons.length > 0) {
        togglePasswordButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentNode.querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    }
    
    // Validation du formulaire d'inscription (déplacé depuis inscription.php)
    const inscriptionForm = document.getElementById('inscription-form');
    if (inscriptionForm) {
        const passwordInput = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const togglePassword = document.getElementById('toggle-password');
        
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Changer l'icône
                const icon = this.querySelector('i');
                if (type === 'text') {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        }
        
        if (confirmPassword) {
            inscriptionForm.addEventListener('submit', function(event) {
                if (passwordInput.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Les mots de passe ne correspondent pas');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            });
            
            confirmPassword.addEventListener('input', function() {
                if (passwordInput.value !== this.value) {
                    this.setCustomValidity('Les mots de passe ne correspondent pas');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    }
});