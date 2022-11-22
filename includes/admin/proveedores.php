<?php
//Igual no deberías poder abrirme
defined( 'ABSPATH' ) || exit;

//Envía el mensaje SMS
function tamatay_social_sms_envia_sms( $tamatay_social_sms_settings, $telefono, $mensaje, $estado, $propietario = false ) {
    //Gestiona los estados
	switch( $estado ) {
		case "on-hold":
            $estado    = ( $propietario ) ? "mensaje_pedido" : "mensaje_recibido";
            
            break;
		case "pending":
            $estado    = "mensaje_pendiente";
            
            break;
		case "failed":
            $estado    = "mensaje_fallido";
            
            break;
		case "processing":
            $estado    = ( $propietario ) ? "mensaje_pedido" : "mensaje_procesando";
            
            break;
		case "completed":
            $estado    = "mensaje_completado";
            
            break;
		case "refunded":
            $estado    = "mensaje_devuelto";
            
            break;
		case "cancelled":
            $estado    = "mensaje_cancelado";
            
            break;
    }

    //Gestiona los proveedores
	switch ( $tamatay_social_sms_settings[ 'servicio' ] ) {
		case "msg91":
            $argumentos[ 'body' ]		= [ 
                'authkey' 					=> $tamatay_social_sms_settings[ 'clave_msg91' ],
                'mobiles' 					=> $telefono,
                'message' 					=> tamatay_social_sms_codifica_el_mensaje( tamatay_social_sms_normaliza_mensaje( $mensaje ) ),
                'sender' 					=> $tamatay_social_sms_settings[ 'identificador_msg91' ],
                'route' 					=> $tamatay_social_sms_settings[ 'ruta_msg91' ],
            ];
            //DLT
            if ( $tamatay_social_sms_settings[ 'dlt_msg91' ] ) { //Sólo si existe el valor
 				$argumentos[ 'body' ][ 'DLT_TE_ID' ] = $tamatay_social_sms_settings[ 'dlt_' . $estado ];
            }
            
			$respuesta					= wp_remote_post( "https://api.msg91.com/api/sendhttp.php", $argumentos );
            
			break;
		case "waapi":
 			$url						= add_query_arg( [
 				'client_id'					=> $tamatay_social_sms_settings[ 'usuario_waapi' ],
 				'instance'					=> $tamatay_social_sms_settings[ 'contrasena_waapi' ],
				'type'						=> 'text',
				'number'					=> $telefono,
 				'message'					=> tamatay_social_sms_codifica_el_mensaje( $mensaje ),
 			], 'https://app.tamatay.com/api/send.php' );
            
 			$respuesta					= wp_remote_get( $url );
            
			break;
	}

    //Envía el correo con el informe
	if ( isset( $tamatay_social_sms_settings[ 'debug' ] ) && $tamatay_social_sms_settings[ 'debug' ] == "1" && isset( $tamatay_social_sms_settings[ 'campo_debug' ] ) ) {
		$correo	= __( 'Mobile number:', 'woocommerce-tamatay-social-sms-notifications' ) . "\r\n" . $telefono . "\r\n\r\n";
		$correo	.= __( 'Message: ', 'woocommerce-tamatay-social-sms-notifications' ) . "\r\n" . $mensaje . "\r\n\r\n"; 
        if ( isset( $argumentos ) ) {
            $correo	.= __( 'Arguments: ', 'woocommerce-tamatay-social-sms-notifications' ) . "\r\n" . print_r( $argumentos, true );
        }
		$correo	.= __( 'Gateway answer: ', 'woocommerce-tamatay-social-sms-notifications' ) . "\r\n" . print_r( $respuesta, true );
		wp_mail( $tamatay_social_sms_settings[ 'campo_debug' ], 'WC - Tamatay Social SMS Notifications', $correo, 'charset=UTF-8' . "\r\n" ); 
	}
}
