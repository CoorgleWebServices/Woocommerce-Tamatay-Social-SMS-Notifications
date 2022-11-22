<?php
//Igual no deberías poder abrirme
defined( 'ABSPATH' ) || exit;

//Definimos las variables
$tamatay_social_sms = [ 	
	'plugin' 		=> 'WC - Tamatay Social SMS Notifications', 
	'plugin_uri' 	=> 'woocommerce-tamatay-social-sms-notifications', 
	'donacion' 		=> 'https://cws.coorgle.com/',
	'soporte' 		=> 'https://cws.coorgle.com/submitticket.php?step=2&deptid=36',
	'plugin_url' 	=> 'https://www.tamatay.com/', 
	'ajustes' 		=> 'admin.php?page=tamatay_social_sms', 
	'puntuacion' 	=> 'https://wordpress.org/support/view/plugin-reviews/woocommerce-tamatay-social-sms-notifications' 
];

//Carga el idioma
load_plugin_textdomain( 'woocommerce-tamatay-social-sms-notifications', null, dirname( DIRECCION_tamatay_social_sms ) . '/languages' );

//Carga la configuración del plugin
$tamatay_social_sms_settings = get_option( 'tamatay_social_sms_settings' );

//Enlaces adicionales personalizados
function tamatay_social_sms_enlaces( $enlaces, $archivo ) {
	global $tamatay_social_sms;

	if ( $archivo == DIRECCION_tamatay_social_sms ) {
		$enlaces[] = '<a href="https://www.tamatay.com/" target="_blank" title="' . $tamatay_social_sms[ 'plugin' ] . '"><strong class="coorglewebservices">Tamatay Social</strong></a>';
		$enlaces[] = '<a href="https://www.facebook.com/app.Tamatay/" title="' . __( 'Follow us on ', 'woocommerce-tamatay-social-sms-notifications' ) . 'Facebook" target="_blank"><span class="genericon genericon-facebook-alt"></span></a> <a href="https://twitter.com/Tamatay_com" title="' . __( 'Follow us on ', 'woocommerce-tamatay-social-sms-notifications' ) . 'Twitter" target="_blank"><span class="genericon genericon-twitter"></span></a> <a href="https://www.linkedin.com/company/tamatay/" title="' . __( 'Follow us on ', 'woocommerce-tamatay-social-sms-notifications' ) . 'LinkedIn" target="_blank"><span class="genericon genericon-linkedin"></span></a>';
		$enlaces[] = '<a href="https://profiles.wordpress.org/coorgle/" title="' . __( 'More plugins on ', 'woocommerce-tamatay-social-sms-notifications' ) . 'WordPress" target="_blank"><span class="genericon genericon-wordpress"></span></a>';
		$enlaces[] = '<a href="https://cws.coorgle.com/submitticket.php?step=2&deptid=36" title="' . __( 'Contact with us by ', 'woocommerce-tamatay-social-sms-notifications' ) . 'e-mail"><span class="genericon genericon-mail"></span></a>';
		$enlaces[] = tamatay_social_sms_plugin( $tamatay_social_sms[ 'plugin_uri' ] );
	}

	return $enlaces;
}
add_filter( 'plugin_row_meta', 'tamatay_social_sms_enlaces', 10, 2 );

//Añade el botón de configuración
function tamatay_social_sms_enlace_de_ajustes( $enlaces ) { 
	global $tamatay_social_sms;

	$enlaces_de_ajustes = [ 
		'<a href="' . $tamatay_social_sms[ 'ajustes' ] . '" title="' . __( 'Settings of ', 'woocommerce-tamatay-social-sms-notifications' ) . $tamatay_social_sms[ 'plugin' ] .'">' . __( 'Settings', 'woocommerce-tamatay-social-sms-notifications' ) . '</a>', 
		'<a href="' . $tamatay_social_sms[ 'soporte' ] . '" title="' . __( 'Support of ', 'woocommerce-tamatay-social-sms-notifications' ) . $tamatay_social_sms[ 'plugin' ] .'">' . __( 'Support', 'woocommerce-tamatay-social-sms-notifications' ) . '</a>' 
	];
	foreach( $enlaces_de_ajustes as $enlace_de_ajustes )	{
		array_unshift( $enlaces, $enlace_de_ajustes );
	}

	return $enlaces; 
}
$plugin = DIRECCION_tamatay_social_sms; 
add_filter( "plugin_action_links_$plugin", 'tamatay_social_sms_enlace_de_ajustes' );

//Obtiene toda la información sobre el plugin
function tamatay_social_sms_plugin( $nombre ) {
	global $tamatay_social_sms;
	
	$respuesta	= get_transient( 'tamatay_social_sms_plugin' );
	if ( false === $respuesta ) {
		$respuesta = wp_remote_get( 'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=' . $nombre  );
		set_transient( 'tamatay_social_sms_plugin', $respuesta, 24 * HOUR_IN_SECONDS );
	}
	if ( ! is_wp_error( $respuesta ) ) {
		$plugin = json_decode( wp_remote_retrieve_body( $respuesta ) );
	} else {
	   return '<a title="' . sprintf( __( 'Please, rate %s:', 'woocommerce-tamatay-social-sms-notifications' ), $tamatay_social_sms[ 'plugin' ] ) . '" href="' . $tamatay_social_sms[ 'puntuacion' ] . '?rate=5#postform" class="estrellas">' . __( 'Unknown rating', 'woocommerce-tamatay-social-sms-notifications' ) . '</a>';
	}
	// this section will be enabled after the plugin is approved by wordpress for rating purpose
    // $rating = [
	//    'rating'		=> $plugin->rating,
	//    'type'		=> 'percent',
	//    'number'		=> $plugin->num_ratings,
	// ];
	// ob_start();
	// wp_star_rating( $rating );
	// $estrellas = ob_get_contents();
	// ob_end_clean();

	// return '<a title="' . sprintf( __( 'Please, rate %s:', 'woocommerce-tamatay-social-sms-notifications' ), $tamatay_social_sms[ 'plugin' ] ) . '" href="' . $tamatay_social_sms[ 'puntuacion' ] . '?rate=5#postform" class="estrellas">' . $estrellas . '</a>';
}

//Hoja de estilo
function tamatay_social_sms_estilo() {
	if ( strpos( $_SERVER[ 'REQUEST_URI' ], 'tamatay_social_sms' ) !== false || strpos( $_SERVER[ 'REQUEST_URI' ], 'plugins.php' ) !== false ) {
		wp_register_style( 'tamatay_social_sms_hoja_de_estilo', plugins_url( 'assets/css/style.css', DIRECCION_tamatay_social_sms ) ); //Carga la hoja de estilo
		wp_enqueue_style( 'tamatay_social_sms_hoja_de_estilo' ); //Carga la hoja de estilo
	}
}
add_action( 'admin_enqueue_scripts', 'tamatay_social_sms_estilo' );
