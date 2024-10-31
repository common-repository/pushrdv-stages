<?php

/**
* Plugin Name: PushRDV Stages
* Description: Permet l'affichage des stages que vous proposez via la plateforme de prise de rendez-vous en ligne <a href="http://www.pushrdv.com/" target="_blank">PushRDV</a>
* Version: 1.0
* Author: Keole
* Author URI: www.keole.net
*/

//-------------- Activation functions ------------------
register_activation_hook( __FILE__, 'pushrdv_plugin_activation' ); //Activation du plugin
add_action( 'admin_menu', 'pushrdv_admin_menu' ); //Initialisation de l'admin : Ajout du menu

/**
 * Création des tables si elles n'existent pas encore.
 */
function pushrdv_plugin_activation(){
    global $wpdb;
    $wpdb->query('
    CREATE TABLE IF NOT EXISTS `'.$wpdb->prefix.'pushrdv_auth`(
        `customer_id` bigint(20) NOT NULL,
        `customer_pkey` varchar(20) NOT NULL,
        `base_url` varchar(50) NOT NULL,
        PRIMARY KEY (`customer_id`))
    ');
}

add_action( 'init', 'pushrdv_buttons' );
function pushrdv_buttons() {
    add_filter( "mce_external_plugins", "pushrdv_add_buttons" );
    add_filter( 'mce_buttons', 'pushrdv_register_buttons' );
}
function pushrdv_add_buttons( $plugin_array ) {
    $plugin_array['pushrdv_stages'] = plugin_dir_url('pushrdv_stages/pushrdv.png')."pushrdv_stages.js";
    return $plugin_array;
}
function pushrdv_register_buttons( $buttons ) {
    array_push( $buttons, "|",'pushrdv_stages' );
    return $buttons;
}

/**
 * Création du menu Organisations
 */
function pushrdv_admin_menu(){
    add_menu_page( 'PushRDV', 'PushRDV', 'manage_options', 'pushrdv_stages/pushrdv-admin.php', '', plugin_dir_url('pushrdv_stages/pushrdv.png')."pushrdv.png", 50 );
}

//-------------- Customer Authentification functions ------------------
add_action( 'wp_ajax_pushrdv_create_customer_authentification', 'createCustomerAuthAction' );
add_action( 'wp_ajax_nopriv_pushrdv_create_customer_authentification', 'createCustomerAuthAction' );
function createCustomerAuthAction(){
    global $wpdb;
    if(isset($_POST['private_key']) && isset($_POST['customer_id']) && isset($_POST['base_url'])){
        if($_POST['base_url'] == ''){
            $_POST['base_url'] = 'http://wbd.pushrdv.com';
        }else{
            if(substr_count($_POST['base_url'], 'http://') != 1){
                $_POST['base_url'] = 'http://'.$_POST['base_url'];
            }
            $_POST['base_url'] = rtrim($_POST['base_url'], '/');
        }
        $response = checkCustomerAuth($_POST['customer_id'], $_POST['private_key'], $_POST['base_url']);
        if(isset($response['ok'])){
            $wpdb->query('INSERT INTO `'.$wpdb->prefix.'pushrdv_auth`(`customer_id`, `customer_pkey`, `base_url`) VALUES ('.$_POST['customer_id'].',"'.$_POST['private_key'].'", "'.$response['customer']['reseller']['baseUrl'].'")');
        }
        echo(json_encode($response));
        die;
    }
    echo('error');
    die;
}
function checkCustomerAuthAction(){
    $auth = isAuth();
    if($auth != false){
        $response = checkCustomerAuth($auth['customer_id'], $auth['customer_pkey'], $auth['base_url']);
        if($response['ok']){
            return true;
        }else{
            return false;
        }
    }else{
        return false;
    }
}

function isAuth(){
    global $wpdb;
    $auth = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."pushrdv_auth` WHERE 1", ARRAY_A);
    if(!empty($auth)){
        return $auth;
    }else{
        return false;
    }
}

//---------------------------- CURL & REST Functions --------------------------------------
/**
 * @param $customer_id
 * @param $private_key
 * @return Success or error
 */
function checkCustomerAuth($customer_id, $private_key, $base_url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('private_key' => $private_key)));
    curl_setopt($ch, CURLOPT_URL, $base_url.'/customer_rest/authentification/'.$customer_id);
    $result=curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
}

add_action( 'wp_ajax_pushrdv_get_agency', 'getAgencyAjaxAction' );
add_action( 'wp_ajax_nopriv_pushrdv_get_agency', 'getAgencyAjaxAction' );
function getAgencyAjaxAction(){
    $response = getCustomerAgencys();
    echo(json_encode($response));
    die;
}

/**
 * @return array of agencys formatted as array
 */
function getCustomerAgencys(){
    $auth = isAuth();
    if($auth != false){
        if(checkCustomerAuthAction()){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('private_key' => $auth['customer_pkey'])));
            curl_setopt($ch, CURLOPT_URL, $auth['base_url'].'/customer_rest/get/agencys/'.$auth['customer_id']);
            $result=curl_exec($ch);
            curl_close($ch);

            return json_decode($result, true);
        }
    }
    return array('error' => 'Erreur d\'authentification');
}

function getCustomerStages($limit){
    $auth = isAuth();
    if($auth != false){
        if(checkCustomerAuthAction()){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('private_key' => $auth['customer_pkey'])));
            curl_setopt($ch, CURLOPT_URL, $auth['base_url'].'/stage_rest/get/customer/stages/'.$auth['customer_id'].'/'.$limit);
            $result=curl_exec($ch);
            curl_close($ch);

            return json_decode($result, true);
        }
    }
    return array('error' => 'Erreur d\'authentification, rendez-vous sur la page PushRDV dans l\'admin de votre site pour vous authentifer sur la plateforme PushRDV');
}

/**
 * GetAgencyStages
 * @param $agency_shortname
 * @return array of stages formatted as array
 */
function getAgencyStages($limit, $agency_shortname){
    $auth = isAuth();
    if($auth != false){
        if(checkCustomerAuthAction()){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('private_key' => $auth['customer_pkey'])));
            curl_setopt($ch, CURLOPT_URL, $auth['base_url'].'/stage_rest/get/agency/stages/'.$auth['customer_id'].'/'.$limit.'/'.$agency_shortname);
            $result=curl_exec($ch);
            curl_close($ch);

            return json_decode($result, true);
        }
    }
    return array('error' => 'Erreur d\'authentification, rendez-vous sur la page PushRDV dans l\'admin de votre site pour vous authentifer sur la plateforme PushRDV');
}
//-------------- Admin Display Functions ------------
function makeCustomerAgencys() {
    $agencys = getCustomerAgencys();
    if(!isset($agencys['error'])){
        if(count($agencys) > 0 ){
            echo('<ul>');
            foreach($agencys as $agency){
                echo('<li style="list-style-type: circle;margin-left: 20px;">'.$agency['name'].'</li>');
            }
            echo('</ul>');
        }else{
            echo('<strong>Vous n\'avez pas encore créé d\'agence. Rendez vous dans votre interface d\'administration sur la plateforme <a href="http://wbd.pushrdv.com/">PushRDV</a> pour créer votre première agence.</strong>');
        }
    }else{
        echo('<strong>'.$agencys['error'].'</strong>');
    }
}

//-------------- FrontEnd Display Functions & shortcodes ------------------
function makeAgencyStages($atts, $content = null) {
    wp_register_style( 'pushrdv_stages_style', plugin_dir_url('pushrdv_stages/pushrdv.png').'pushrdv_stages.css' );
    wp_enqueue_style('pushrdv_stages_style');

    extract( shortcode_atts( array(
        'agency_shortname' => 0,
        'limit' => 10,
        'background' => '#fff',
        'main_color' => '#3467B1'
    ), $atts ) );

    $auth = isAuth();
    if($auth != false){
        $style = '';
        if($background != '#fff' || $main_color != '#3467B1'){

            $style = '<style media="all" type="text/css">
            .stage-date, .stage-prices{color: '.$background.' !important; background-color: '.$main_color.' !important;}
            .stage-content{color: '.$main_color.' !important; background-color: '.$background.' !important;}
            .stage-content:hover{color: '.$background.' !important; background-color: '.$main_color.' !important;}
            .stage-content a{color: '.$main_color.' !important;}
            .stage-btn{color: '.$main_color.' !important; background-color: '.$background.' !important;}
            .stage-btn:hover{color: '.$background.' !important; background-color: '.$main_color.' !important;}
        </style>';

        }

        if($agency_shortname == 'all'){
            $stages = getCustomerStages($limit);
        }else{
            $stages = getAgencyStages($limit, $agency_shortname);
        }

        $agencys = getCustomerAgencys();

        if(!$stages['error']){

            if($style != ''){
                $response = $style.'<br><ul class="stage-list">';
            }else{
                $response = '<ul class="stage-list">';
            }

            foreach($stages as $stage){
                $stage_agency = false;
                foreach($agencys as $agency){
                    if($agency['id'] == $stage['agencyId']){
                        $stage_agency = $agency;
                    }
                }

                $startDate = new \DateTime($stage['startDate']);
                setlocale(LC_TIME, 'fr_FR.UTF-8');
                $startMonth = strftime('%B', mktime(0, 0, 0, $startDate->format('m')));
                $endDate = new \DateTime($stage['endDate']);

                if(strlen($stage['description'])>=75){

                    $description = substr($stage['description'], 0, 72).'...';

                }else{

                    $description = $stage['description'];

                }

                $response .= '
                    <li class="stage" itemscope="" itemprop="event" itemtype="http://schema.org/Event">
                        <span class="stage-date">
                            <div class="stage-day">'.$startDate->format("d").'</div>
                            <div class="stage-month">'.ucfirst(strftime("%B", mktime(0, 0, 0, $startDate->format("m")))).'</div>
                            <div class="stage-year">'.$startDate->format("Y").'</div>
                        </span>
                        <span itemprop="url" class="hideItem">'.$auth['base_url'].'/stage/'.$stage_agency["shortName"].'/'.$stage["id"].'</span>
                        <div class="stage-place" class="hideItem" itemprop="location" itemscope="" itemtype="http://schema.org/Place">
                            <span itemprop="name" class="hideItem">'.$stage_agency["name"].'</span>
                            <span itemprop="address" itemscope="" itemtype="http://schema.org/PostalAddress">
                                <span itemprop="streetAddress"  class="hideItem">'.$stage_agency["street"].'</span><span itemprop="postalCode" class="hideItem">'.$stage_agency["postal"].'</span><span class="hideItem" itemprop="addressLocality">'.$stage_agency["city"].'</span><span class="hideItem" itemprop="addressCountry">FR</span>
                            </span>
                        </div>
                        <div>
                            <meta itemprop="startDate" content="'.$startDate->format("Y-m-d").'T'.$startDate->format("YH:i:s").'"/>
                            <meta itemprop="endDate" content="'.$endDate->format("Y-m-d").'T'.$endDate->format("YH:i:s").'"/>
                        </div>
                        <a href="'.$auth['base_url'].'/stage/'.$stage_agency["shortName"].'/'.$stage["id"].'" target="_blank" class="stage-content">
                            <div class="stage-title"><span itemprop="name">'.$stage["name"].'</span> - '.$stage_agency["city"].'</div>
                            <div itemprop="description" class="stage-description">'.$description.'</div>
                            <div class="stage-dates">Du '.$startDate->format("d/m/Y").' au '.$endDate->format("d/m/Y").' - '.$stage_agency["name"].'</div>
                        </a>';

                if($stage['price'] > 0){

                    $response .= '<span class="stage-prices" itemprop="offers" data-line="1" itemscope itemtype="http://schema.org/AggregateOffer">
                            <div class="stage-price"><span itemprop="price">'.$stage["price"].'</span> €</div>
                            <span itemprop="lowprice" class="hideItem">'.$stage["price"].'</span>
                            <span itemprop="priceCurrency" class="hideItem">EUR</span>
                            <div class="stage-month"><span itemprop="offerCount">'.$stage["availablePlaces"].'</span> Places restantes</div>
                            <span itemprop="url" class="hideItem">'.$auth['base_url'].'/stage/'.$stage_agency["shortName"].'/'.$stage["id"].'</span>
                            <a href="'.$auth['base_url'].'/stage/log/'.$stage["agencyId"].'/'.$stage["id"].'" target="_blank" class="stage-btn">Inscription</a>
                        </span>
                    </li>';

                }else{

                    $response .= '<span class="stage-prices" itemprop="offers" data-line="1" itemscope itemtype="http://schema.org/AggregateOffer">
                            <div class="stage-price"><span itemprop="offerCount">'.$stage["availablePlaces"].'</span></div>
                            <span itemprop="lowprice" class="hideItem">NA</span>
                            <span itemprop="priceCurrency" class="hideItem">EUR</span>
                            <div class="stage-month">Places restantes</div>
                            <a href="'.$auth['base_url'].'/stage/log/'.$stage["agencyId"].'/'.$stage["id"].'" target="_blank" class="stage-btn">Inscription</a>
                        </span>
                    </li>';

                }
            }
            $response .= '</ul>';
            return $response;

        }else{

            return '<div style="background-color: white; color: darkred"><strong>'.$stages['error'].'</strong></div>';

        }
    }
}

add_shortcode("agencyStages","makeAgencyStages");