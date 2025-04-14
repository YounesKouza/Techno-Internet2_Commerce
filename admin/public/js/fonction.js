$(document).ready(function() {
    // ======================================
    // Fonctions générales
    // ======================================
    
    // Fonction pour mettre à jour le compteur du panier
    function updateCartCount(count) {
        $('.cart-count').text(count);
        if (count > 0) {
            $('.cart-count').show();
        } else {
            $('.cart-count').hide();
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
            error: function() {
                showNotification('Erreur de communication avec le serveur', 'error');
            }
        });
    });
    
    // Supprimer un produit du panier
    $('.remove-from-cart').click(function(e) {
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
                    $('.cart-total').text(response.total.toFixed(2) + ' €');
                    
                    // Si le panier est vide, afficher un message
                    if (response.items.length === 0) {
                        $('.cart-items').html('<p>Votre panier est vide.</p>');
                    }
                }
            }
        });
    }
    
    // ======================================
    // Fonctions spécifiques aux pages admin
    // ======================================
    
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
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
        
        // Prévisualisation de l'image
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#preview-image').attr('src', e.target.result).show();
            }
            reader.readAsDataURL(this.files[0]);
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
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
            e.preventDefault();
        }
    });
    
    // Prévisualisation des images pour l'ajout/modification de meubles
    $('#image_principale, #images').on('change', function() {
        const inputId = $(this).attr('id');
        const previewContainer = $('#image-preview');
        
        // Vider le conteneur de prévisualisation si c'est l'image principale
        if (inputId === 'image_principale') {
            previewContainer.empty();
        }
        
        // Gestion des fichiers sélectionnés
        if (this.files && this.files.length > 0) {
            // Supprimer le message par défaut
            previewContainer.find('.text-muted').remove();
            
            // Parcourir les fichiers et créer des prévisualisations
            for (let i = 0; i < this.files.length; i++) {
                const file = this.files[i];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const previewElement = $(`
                        <div class="position-relative me-2 mb-2">
                            <img src="${e.target.result}" alt="Aperçu" class="img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                            <div class="position-absolute top-0 start-0 bg-light px-2 py-1 small">
                                ${inputId === 'image_principale' ? 'Principale' : 'Additionnelle'}
                            </div>
                        </div>
                    `);
                    previewContainer.append(previewElement);
                };
                
                reader.readAsDataURL(file);
            }
        }
    });
});