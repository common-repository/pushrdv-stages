<?php
    include_once('pushrdv.php');
    echo('
        <style type="text/css" media="screen">
            .push-btn{padding: 5px; background-color:#3467B1; color: white; font-weight: 600;text-decoration: none;}
            .push-btn:hover{background-color: #30302F; color: white;}
        </style>
        <div class="wrap">
            <a href="http://www.pushrdv.com/" target="_blank" style="display: inline-block"><img  style="display: inline-block" src="'.plugin_dir_url(__FILE__).'logo.png"></a>
            <div style="display: inline-block; vertical-align: top; margin-left: 20px;">
                <h3 style="color:#3467B1; margin: 0">Votre plateforme de prise de rendez-vous en ligne</h3>
                <p>Pour accéder aux fonctionnalités proposées par ce module vous devez posséder un compte entreprise sur l\'application de gestion d\'agendas professionnels et de prise de rendez-vous en ligne PushRDV. </p>
                <a href="http://www.pushrdv.com/" target="_blank" class="push-btn">Accéder à la plateforme PushRDV</a>
            </div>
        </div>
        <br>
        <hr>');
    if(checkCustomerAuthAction()){
        echo('
            <div class="wrap">
                <h2>Vous êtes authentifié</h2>
                <p>Voici les agences dans lesquelles vous pouvez activer le module de stages :</p>');
                makeCustomerAgencys();
        echo('
                <hr>
                <p>Pour afficher les stages d\'une des agences, cliquez sur le bouton <img src="'.plugin_dir_url(__FILE__).'pushrdv2.png"> présent dans l\'éditeur de contenu wordpress afin de paramétrer le <a href="https://openclassrooms.com/courses/propulsez-votre-site-avec-wordpress/les-shortcodes" target="_blank">Shortcode</a> qui va afficher la liste des stages.
                 Sélectionnez une agence, personnalisez les couleurs du bloc et générez le shortcode.</p>
                 <p><em>En cas de problèmes techniques veuillez nous contacter via l\'adresse suivante : <a href="mailto:support@wbd-sas.com">support@wbd-sas.com</a></em></p>
            </div>');
    }else{
        echo('
            <div class="wrap">
                <h2>Authentification</h2>
                <p><em>Vous pouvez retrouver les informations d\'authentification suivantes dans les "Paramètres" de votre entreprise sur la plateforme <strong>PushRDV</strong> dans l\'onglet "Préférences générales".</em></p>
                <table>
                    <tr>
                        <td><label for="customer_id"><strong>ID de votre entreprise * :</strong></label></td>
                        <td><input type="text" name="customer_id" id="customer_id" required="required" size="5"></td>
                    </tr>
                    <tr>
                        <td><label for="private_key"><strong>Clé privée * :</strong></label></td>
                        <td><input type="text" name="private_key" id="private_key" required="required" size="10"></td>
                    </tr>
                    <tr>
                        <td><label for="base_url"><strong>Url de votre plateforme (si différente de http://wbd.pushrdv.com) :</strong></label></td>
                        <td><input type="text" name="base_url" id="base_url" size="25" placeholder="http://wbd.pushrdv.com"></td>
                    </tr>
                </table>
                <br><br>
                <a href="javascript:;" id="pushrdv_authentification" class="button button-primary button-large">Se connecter</a>
                <div id="authentification_progress" style="display:none;"><img src="/wp-admin/images/wpspin_light.gif"><em> Authentification en cours... (Cette action peut durer quelques secondes)</em></div>
                <h3 id="authentification_ok" style="color:darkgreen; margin-bottom: 3px; margin-top: 3px; display:none;">Authentification Réussie, vous pouvez maintenant vous servir du plugin.</h3>
                <h3 id="authentification_error" style="color:darkred; margin-bottom: 3px; margin-top: 3px; display:none;"></h3>
            </div>
        ');
    }
?>
<script type="text/javascript" >
    jQuery(document).ready(function($) {
        $('#pushrdv_authentification').click(function () {
            $('#authentification_error').hide();
            if($('#customer_id').val() != '' && $('#customer_id').val().length <= 5){
                if($('#private_key').val() != '' && $('#private_key').val().length == 10){
                    $('#pushrdv_authentification').hide();
                    $('#authentification_progress').show();
                    var data = {
                        'action': 'pushrdv_create_customer_authentification',
                        'customer_id': $('#customer_id').val(),
                        'private_key': $('#private_key').val(),
                        'base_url': $('#base_url').val()
                    };
                    // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                    $.ajax({
                        type: "POST",
                        url: ajaxurl,
                        data: data,
                        dataType: "json",
                        success: function(response){
                            $('#authentification_progress').hide();
                            if(response.ok){
                                window.location.reload(true);
                            }else{
                                $('#authentification_error').html(response.error);
                                $('#authentification_error').show();
                                $('#pushrdv_authentification').show();
                            }
                        }
                    })
                }else{
                    $('#authentification_error').html('Veuillez saisir une clé privée valide.');
                    $('#authentification_error').show();
                    $('#pushrdv_authentification').show();
                }
            }else{
                $('#authentification_error').html('Veuillez saisir un ID valide.');
                $('#authentification_error').show();
            }
        });
    });
</script>