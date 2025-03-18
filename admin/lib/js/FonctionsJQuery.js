$(document).ready(function () {

    $('#texte_submit').text('Ajouter au panier');
    $('#nom_produith').blur(function () {
        let nom = $.trim($(this).val());
        let parametre = 'nom_produith=' + nom;
        let retour = $.ajax({
            type: 'GET',
            data: parametre,
            dataType: 'json',
            url: './lib/php/ajax/ajaxVerifierProduitHomme.php'
        });
    });

    $('#texte_submit').text('Ajouter au panier');
    $('#nom_produitf').blur(function () {
        let nom = $.trim($(this).val());
        let parametre = 'nom_produitf=' + nom;
        let retour = $.ajax({
            type: 'GET',
            data: parametre,
            dataType: 'json',
            url: './lib/php/ajax/ajaxVerifierProduitFemme.php'
        });
    });

    /* Tableau editable */
    //interception du clic sur le champ dont l'id est un id php
    $('span[id]').click(function () {
        var ancien = $.trim($(this).text());//récup de l'ancienne valeur sans blanc autour
        var nom_produith = $(this).attr('name');//attribut name de la ligne
        var id_produith = $(this).attr('id');
        console.log(ancien, nom_produith, id_produith);

        //blur : événement perte de focus
        $(this).blur(function () {

            var nouveau = $.trim($(this).text());

            if (ancien != nouveau) {
                console.log(ancien, nouveau, nom_produith, id_produith);
                var parametre = 'nom_produith=' + nom_produith + '&id_produith=' + id_produith + '&nouveau=' + nouveau;
                console.log(parametre);

                $.ajax({
                    type: 'GET',
                    data: parametre,
                    dataType: 'json',
                    url: './lib/php/ajax/ajaxUpdateProduitHomme.php',
                    success: function (data) {
                        console.log(data);
                    }
                });
            }

        });

    });

    $('span[id]').click(function () {
        var ancien = $.trim($(this).text());//récup de l'ancienne valeur sans blanc autour
        var nom_produitf = $(this).attr('name');//attribut name de la ligne
        var id_produitf = $(this).attr('id');
        console.log(ancien, nom_produitf, id_produitf);

        //blur : événement perte de focus
        $(this).blur(function () {

            var nouveau = $.trim($(this).text());

            if (ancien != nouveau) {
                console.log(ancien, nouveau, nom_produitf, id_produitf);
                var parametre = 'champ=' + nom_produitf + '&id=' + id_produitf + '&nouveau=' + nouveau;
                console.log(parametre);
                //alert(parametre);
                $.ajax({
                    type: 'GET',
                    data: parametre,
                    dataType: 'json',
                    url: './lib/php/ajax/ajaxUpdateProduitFemme.php',
                    success: function (data) {
                        console.log(data);
                    }
                });
            }

        });

    });

    /* Qui_js_explore.php  */
    $('.titre').css('color', 'red');
    $('.titre').hide();
    $('.titre').fadeIn(3000);
    $('.titre').css({
        'font-weight': 'bold',
        'font-size': '130%',
        'text-transform': 'capitalize'
    });
    $('.titre2').css('color', 'lightskyblue');
    $('.titre2').hide();
    $('.titre2').fadeIn(4000);
    $('.titre2').css({
        'font-weight': 'bold',
        'font-size': '130%',
        'text-transform': 'capitalize'
    });

    /* produits */
    $('.titreProduits').css('color', 'black');
    $('.titreProduits').hide();
    $('.titreProduits').fadeIn(3000);
    $('.titreProduits').css({
        'font-weight': 'bold',
        'font-size': '150%',
        'text-transform': 'capitalize',
        'text-align': 'center',
        'margin': '10px 0 10px 0'
    });

    /* Catégorie Produits */
    $('.titreProduits2').css('color', 'white');
    $('.titreProduits2').hide();
    $('.titreProduits2').fadeIn();
    $('.titreProduits2').css({
        'font-weight': 'bold',
        'font-size': '150%',
        'text-transform': 'capitalize',
        'text-align': 'center',
    });

    /* paniers */
    $('.titrePanier').css('color', 'green');
    $('.titrePanier').hide();
    $('.titrePanier').fadeIn(3000);
    $('.titrePanier').css({
        'font-weight': 'bold',
        'font-size': '150%',
        'text-transform': 'capitalize',
        'text-align': 'center',
        'margin': '10px 0 10px 0'
    });

    $('.titrePanier2').css('color', 'green');
    $('.titrePanier2').css({
        'font-weight': 'bold',
        'font-size': '150%',
        'text-transform': 'capitalize',
        'text-align': 'left',
        'margin': '10px 0 10px 0'
    });

    $('.titreCol').css('color', 'black');
    $('.titreCol').css({
        'text-transform': 'capitalize',
        'text-align': 'center'
    });

    $('.colonne').css('color', 'black');
    $('.colonne').css({
        'text-transform': 'capitalize',
        'text-align': 'center'
    });

    /* Client Modifiable */
    $("button[id='submit_client']").click(function () {

        var nom_client = $("#nom_client")[0].value;
        var prenom_client = $("#prenom_client")[0].value;
        var email = $("#email")[0].value;
        var adresse = $("#adresse")[0].value;
        var code_postal = $("#code_postal")[0].value;
        var ville = $("#ville")[0].value;
        var id_client = $(this).attr('value');

        var parametre = "id_client=" + id_client + '&nom_client=' + nom_client + '&prenom_client=' + prenom_client + '&email=' + email + '&adresse=' + adresse + '&code_postal=' + code_postal + '&ville=' + ville;
        $.ajax({
            type: 'GET',
            data: parametre,
            dataType: 'json',
            url: './lib/php/ajax/ajaxUpdateClientData.php',
            success: function (data) {
                window.location.reload();
            },
            error: (e) => {
                console.log("NULL", e);
            }
        });

    });

});