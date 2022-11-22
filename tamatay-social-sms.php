<?php
/*
Plugin Name: WC - Tamatay Social SMS Notifications
Version: 2.25
Plugin URI: https://wordpress.org/plugins/woocommerce-tamatay-social-sms-notifications/
Description: Add to WooCommerce SMS notifications to your clients for order status changes. Also you can receive an SMS message when the shop get a new order and select if you want to send international SMS. The plugin add the international dial code automatically to the client phone number.
Author URI: https://cws.coorgle.com/
Author: Coorgle Web Services
Requires at least: 3.8
Tested up to: 6.1
WC requires at least: 2.1
WC tested up to: 6.7

Text Domain: woocommerce-tamatay-social-sms-notifications
Domain Path: /languages

@package WC - Tamatay Social SMS Notifications
@category Core
@author Coorgle Web Services
*/

//Igual no deberías poder abrirme
defined( 'ABSPATH' ) || exit;

//Definimos constantes
define( 'DIRECCION_tamatay_social_sms', plugin_basename( __FILE__ ) );

//Funciones generales de Tamatay
include_once( 'includes/admin/funciones-apg.php' );

//¿Está activo WooCommerce?
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) || is_network_only_plugin( 'woocommerce/woocommerce.php' ) ) {
	//Cargamos funciones necesarias
	include_once( 'includes/admin/funciones.php' );

	//Comprobamos si está instalado y activo WPML
	$wpml_activo = function_exists( 'icl_object_id' );
    
    //Mensajes
    $mensajes   = [
        'propietario'   => 'mensaje_pedido',
        'pending'       => 'mensaje_pendiente',
        'failed'        => 'mensaje_fallido',
        'on-hold'       => 'mensaje_recibido',
        'processing'    => 'mensaje_procesando',
        'completed'     => 'mensaje_completado',
        'refunded'      => 'mensaje_devuelto',
        'cancelled'     => 'mensaje_cancelado',
        'nota'          => 'mensaje_nota',
    ];

	//Actualiza las traducciones de los mensajes SMS
	function tamatay_social_registra_wpml( $tamatay_social_sms_settings ) {
		global $wpml_activo, $mensajes;
        
		//Registramos los mensajes en WPML
        foreach ( $mensajes as $mensaje ) {
            if ( $wpml_activo && function_exists( 'icl_register_string' ) ) { //Versión anterior a la 3.2
                icl_register_string( 'tamatay_social_sms', $mensaje, esc_textarea( $tamatay_social_sms_settings[ $mensaje ] ) );
            } else if ( $wpml_activo ) { //Versión 3.2 o superior
                do_action( 'wpml_register_single_string', 'tamatay_social_sms', $mensaje, esc_textarea( $tamatay_social_sms_settings[ $mensaje ] ) );
            }
        }
	}
	
	//Inicializamos las traducciones y los proveedores
	function tamatay_social_sms_inicializacion() {
		global $tamatay_social_sms_settings, $wpml_activo;

        if ( $wpml_activo ) {
		  tamatay_social_registra_wpml( $tamatay_social_sms_settings );
        }
	}
	add_action( 'init', 'tamatay_social_sms_inicializacion' );

	//Pinta el formulario de configuración
	function tamatay_social_sms_tab() {
		include( 'includes/admin/funciones-formulario.php' );
		include( 'includes/formulario.php' );
	}

	//Añade en el menú a WooCommerce
	function tamatay_social_sms_admin_menu() {
		add_submenu_page( 'woocommerce', __( 'Tamatay Social SMS Notifications', 'woocommerce-tamatay-social-sms-notifications' ),  __( 'SMS Notifications', 'woocommerce-tamatay-social-sms-notifications' ) , 'manage_woocommerce', 'tamatay_social_sms', 'tamatay_social_sms_tab' );
	}
	add_action( 'admin_menu', 'tamatay_social_sms_admin_menu', 15 );

	//Carga los scripts y CSS de WooCommerce
	function tamatay_social_sms_screen_id( $woocommerce_screen_ids ) {
		$woocommerce_screen_ids[] = 'woocommerce_page_tamatay_social_sms';

		return $woocommerce_screen_ids;
	}
	add_filter( 'woocommerce_screen_ids', 'tamatay_social_sms_screen_id' );

	//Registra las opciones
	function tamatay_social_sms_registra_opciones() {
		global $tamatay_social_sms_settings;
	
		register_setting( 'tamatay_social_sms_settings_group', 'tamatay_social_sms_settings', 'tamatay_social_sms_update' );
		$tamatay_social_sms_settings = get_option( 'tamatay_social_sms_settings' );
	}
	add_action( 'admin_init', 'tamatay_social_sms_registra_opciones' );
	
	function tamatay_social_sms_update( $tamatay_social_sms_settings ) {
        tamatay_social_registra_wpml( $tamatay_social_sms_settings );
		
		return $tamatay_social_sms_settings;
	}

	//Procesa el SMS
	function tamatay_social_sms_procesa_estados( $numero_de_pedido, $temporizador = false ) {
		global $tamatay_social_sms_settings, $wpml_activo, $mensajes;
		
		$pedido   = new WC_Order( $numero_de_pedido );
		$estado   = is_callable( [ $pedido, 'get_status' ] ) ? $pedido->get_status() : $pedido->status;

		//Comprobamos si se tiene que enviar el mensaje o no
		if ( isset( $tamatay_social_sms_settings[ 'mensajes' ] ) ) {
			if ( $estado == 'on-hold' && ! array_intersect( [ "todos", "mensaje_pedido", "mensaje_recibido" ], $tamatay_social_sms_settings[ 'mensajes' ] ) ) {
				return;
			} else if ( $estado == 'pending' && ! array_intersect( [ "todos", "mensaje_pendiente" ], $tamatay_social_sms_settings[ 'mensajes' ] ) ) {
				return;
			} else if ( $estado == 'failed' && ! array_intersect( [ "todos", "mensaje_fallido" ], $tamatay_social_sms_settings[ 'mensajes' ] ) ) {
				return;
			} else if ( $estado == 'processing' && ! array_intersect( [ "todos", "mensaje_pedido", "mensaje_procesando" ], $tamatay_social_sms_settings[ 'mensajes' ] ) ) {
				return;
			} else if ( $estado == 'completed' && ! array_intersect( [ "todos", "mensaje_completado" ], $tamatay_social_sms_settings[ 'mensajes' ] ) ) {
				return;
			} else if ( $estado == 'refunded' && ! array_intersect( [ "todos", "mensaje_devuelto" ], $tamatay_social_sms_settings[ 'mensajes' ] ) ) {
				return;
			} else if ( $estado == 'cancelled' && ! array_intersect( [ "todos", "mensaje_cancelado" ], $tamatay_social_sms_settings[ 'mensajes' ] ) ) {
				return;
			}
		} else {
			return;
		}

        //Permitir que otros plugins impidan que se envíe el SMS
		if ( ! apply_filters( 'tamatay_social_sms_send_message', true, $pedido ) ) {
			return;
		}

		//Recoge datos del formulario de facturación
		$billing_country		= is_callable( [ $pedido, 'get_billing_country' ] ) ? $pedido->get_billing_country() : $pedido->billing_country;
		$billing_phone			= is_callable( [ $pedido, 'get_billing_phone' ] ) ? $pedido->get_billing_phone() : $pedido->billing_phone;
		$shipping_country		= is_callable( [ $pedido, 'get_shipping_country' ] ) ? $pedido->get_shipping_country() : $pedido->shipping_country;
		$campo_envio			= esc_attr( get_post_meta( $numero_de_pedido, $tamatay_social_sms_settings[ 'campo_envio' ], true ) );
		$telefono				= tamatay_social_sms_procesa_el_telefono( $pedido, $billing_phone, esc_attr( $tamatay_social_sms_settings[ 'servicio' ] ) );
		$telefono_envio			= tamatay_social_sms_procesa_el_telefono( $pedido, $campo_envio, esc_attr( $tamatay_social_sms_settings[ 'servicio' ] ), false, true );
		$enviar_envio			= ( ! empty( $telefono_envio ) && $telefono != $telefono_envio && isset( $tamatay_social_sms_settings[ 'envio' ] ) && $tamatay_social_sms_settings[ 'envio' ] == 1 ) ? true : false;
		$internacional			= ( isset( $billing_country ) && ( WC()->countries->get_base_country() != $billing_country ) ) ? true : false;
		$internacional_envio	= ( isset( $shipping_country ) && ( WC()->countries->get_base_country() != $shipping_country ) ) ? true : false;
        
		//Teléfono propietario
		if ( strpos( $tamatay_social_sms_settings[ 'telefono' ], "|" ) ) { //Existe más de uno
			$administradores = explode( "|", esc_attr( $tamatay_social_sms_settings[ 'telefono' ] ) );
			foreach ( $administradores as $administrador ) {
				$telefono_propietario[]	= tamatay_social_sms_procesa_el_telefono( $pedido, $administrador, esc_attr( $tamatay_social_sms_settings[ 'servicio' ] ), true );
			}
		} else {
			$telefono_propietario = tamatay_social_sms_procesa_el_telefono( $pedido, esc_attr( $tamatay_social_sms_settings[ 'telefono' ] ), esc_attr( $tamatay_social_sms_settings[ 'servicio' ] ), true );	
		}

        //Genera las variables con los textos personalizados
        foreach ( $mensajes as $mensaje ) {
            if ( function_exists( 'icl_register_string' ) || ! $wpml_activo ) { //WPML versión anterior a la 3.2 o no instalado
                $$mensaje   = ( $wpml_activo ) ? icl_translate( 'tamatay_social_sms', $mensaje, esc_textarea( $tamatay_social_sms_settings[ $mensaje ] ) ) : esc_textarea( $tamatay_social_sms_settings[ $mensaje ] );
            } else if ( $wpml_activo ) { //WPML versión 3.2 o superior
                $$mensaje   = apply_filters( 'wpml_translate_single_string', esc_textarea( $tamatay_social_sms_settings[ $mensaje ] ), 'tamatay_social_sms', $mensaje );
            }
        }
        unset( $mensaje ); //Evita mensaje vacío con el temporizador
		
		//Cargamos los proveedores SMS
		include_once( 'includes/admin/proveedores.php' );
        
		//Envía el SMS
        $variables  = esc_textarea( $tamatay_social_sms_settings[ 'variables' ] );
        
        //Mensaje SMS
        if ( $estado == 'on-hold' ) { //Pedido en espera
            //Mensaje para el/los propietarios
            if ( !! array_intersect( [ "todos", "mensaje_pedido" ], $tamatay_social_sms_settings[ 'mensajes' ] ) && isset( $tamatay_social_sms_settings[ 'notificacion' ] ) && $tamatay_social_sms_settings[ 'notificacion' ] == 1 && ! $temporizador ) { //Evita el envío en el temporizador
                if ( ! is_array( $telefono_propietario ) ) {
                    tamatay_social_sms_envia_sms( $tamatay_social_sms_settings, $telefono_propietario, tamatay_social_sms_procesa_variables( $mensaje_pedido, $pedido, $variables ), $estado, true ); //Mensaje para el propietario
                } else {
                    foreach ( $telefono_propietario as $administrador ) {
                        tamatay_social_sms_envia_sms( $tamatay_social_sms_settings, $administrador, tamatay_social_sms_procesa_variables( $mensaje_pedido, $pedido, $variables ), $estado, true ); //Mensaje para los propietarios
                    }
                }
            }
            //Mensaje para el cliente
            if ( !! array_intersect( [ "todos", $mensajes[ $estado ] ], $tamatay_social_sms_settings[ 'mensajes' ] ) ) {
                //Limpia el temporizador para pedidos recibidos
                wp_clear_scheduled_hook( 'tamatay_social_sms_ejecuta_el_temporizador' );

                //Retardo para pedidos recibidos
                if ( isset( $tamatay_social_sms_settings[ 'retardo' ] ) && $tamatay_social_sms_settings[ 'retardo' ] > 0 && ( ! intval( get_post_meta( $numero_de_pedido, 'tamatay_social_sms_retardo_enviado', true ) ) == 1 ) ) {
                    wp_schedule_single_event( time() + ( absint( $tamatay_social_sms_settings[ 'retardo' ] ) * 60 ), 'tamatay_social_sms_ejecuta_el_retraso', [ $numero_de_pedido ] );
                    update_post_meta( $numero_de_pedido, 'tamatay_social_sms_retardo_enviado', -1 );
                } else { //Envío normal
                    $mensaje = tamatay_social_sms_procesa_variables( ${ $mensajes[ $estado ] }, $pedido, $variables ); //Mensaje para el cliente
                }

                //Temporizador para pedidos recibidos
                if ( isset( $tamatay_social_sms_settings[ 'temporizador' ] ) && $tamatay_social_sms_settings[ 'temporizador' ] > 0 ) {
                    wp_schedule_single_event( time() + ( absint( $tamatay_social_sms_settings[ 'temporizador' ] ) * 60 * 60 ), 'tamatay_social_sms_ejecuta_el_temporizador' );
                }
            }            
        } else if ( $estado == 'processing' ) { //Pedido procesando
            //Mensaje para el/los propietarios
            if ( !! array_intersect( [ "todos", "mensaje_pedido" ], $tamatay_social_sms_settings[ 'mensajes' ] ) && isset( $tamatay_social_sms_settings[ 'notificacion' ] ) && $tamatay_social_sms_settings[ 'notificacion' ] == 1 ) {
                if ( ! is_array( $telefono_propietario ) ) {
                    tamatay_social_sms_envia_sms( $tamatay_social_sms_settings, $telefono_propietario, tamatay_social_sms_procesa_variables( $mensaje_pedido, $pedido, $variables ), $estado, true ); //Mensaje para el propietario
                } else {
                    foreach ( $telefono_propietario as $administrador ) {
                        tamatay_social_sms_envia_sms( $tamatay_social_sms_settings, $administrador, tamatay_social_sms_procesa_variables( $mensaje_pedido, $pedido, $variables ), $estado, true ); //Mensaje para los propietarios
                    }
                }
            }
            //Mensaje para el cliente
            if ( !! array_intersect( [ "todos", $mensajes[ $estado ] ], $tamatay_social_sms_settings[ 'mensajes' ] ) ) {
                $mensaje = tamatay_social_sms_procesa_variables( ${ $mensajes[ $estado ] }, $pedido, $variables );
            }            
        } else if ( $estado != 'on-hold' && $estado != 'processing' ) { //El resto de estados
            if ( !! array_intersect( [ "todos", $mensajes[ $estado ] ], $tamatay_social_sms_settings[ 'mensajes' ] ) ) {
                $mensaje = tamatay_social_sms_procesa_variables( ${ $mensajes[ $estado ] }, $pedido, $variables );
            } else {
                $mensaje = tamatay_social_sms_procesa_variables( $tamatay_social_sms_settings[ $estado ], $pedido, $variables );            
            }
        }

        //Se envía el mensaje SMS si no se ha enviado aún
		if ( isset( $mensaje ) && ( ! $internacional || ( isset( $tamatay_social_sms_settings[ 'internacional' ] ) && $tamatay_social_sms_settings[ 'internacional' ] == 1 ) ) ) {
			if ( ! is_array( $telefono ) ) {
				tamatay_social_sms_envia_sms( $tamatay_social_sms_settings, $telefono, $mensaje, $estado ); //Mensaje para el teléfono de facturación
			} else {
				foreach ( $telefono as $cliente ) {
					tamatay_social_sms_envia_sms( $tamatay_social_sms_settings, $cliente, $mensaje, $estado ); //Mensaje para los teléfonos recibidos
				}
			}
			if ( $enviar_envio ) {
				tamatay_social_sms_envia_sms( $tamatay_social_sms_settings, $telefono_envio, $mensaje, $estado ); //Mensaje para el teléfono de envío
			}
		}
	}
	add_action( 'woocommerce_order_status_changed', 'tamatay_social_sms_procesa_estados', 10 ); //Funciona cuando el pedido cambia de estado
	
    //Retraso
 	function tamatay_social_sms_retardo( $numero_de_pedido ) {
 		global $tamatay_social_sms_settings;
        
 		if ( $pedido = wc_get_order( intval( $numero_de_pedido ) ) ) {
 			$retraso_enviado    = get_post_meta( $numero_de_pedido, 'tamatay_social_sms_retardo_enviado', true );
 			$estado             = is_callable( [ $pedido, 'get_status' ] ) ? $pedido->get_status() : $pedido->status;
 			if ( intval( $retraso_enviado ) == -1 ) { //Solo enviamos si no ha cambiado de estado
 				update_post_meta( $numero_de_pedido, 'tamatay_social_sms_retardo_enviado', 1 );		 			
                if ( $estado == 'on-hold' ) {
                    tamatay_social_sms_procesa_estados( $numero_de_pedido );		 				
                    $retraso_enviado    = get_post_meta( $numero_de_pedido, 'tamatay_social_sms_retardo_enviado', true );
                    if ( intval( $retraso_enviado ) == -1 ) {
                        update_post_meta( $numero_de_pedido, 'tamatay_social_sms_retardo_enviado', 1 );
                        tamatay_social_sms_procesa_estados( $numero_de_pedido );
                    }
                }
            }
        }
    }
 	add_action( 'tamatay_social_sms_ejecuta_el_retraso', 'tamatay_social_sms_retardo' );
    
	//Temporizador
	function tamatay_social_sms_temporizador() {
		global $tamatay_social_sms_settings;
		
		$pedidos = wc_get_orders( [
			'limit'			=> -1,
			'date_created'	=> '<' . ( time() - ( absint( $tamatay_social_sms_settings[ 'temporizador' ] ) * 60 * 60 ) - 1 ),
			'status'		=> 'on-hold',
		] );

		if ( $pedidos ) {
			foreach ( $pedidos as $pedido ) {
				tamatay_social_sms_procesa_estados( is_callable( [ $pedido, 'get_id' ] ) ? $pedido->get_id() : $pedido->id, true );
			}
		}
	}
	add_action( 'tamatay_social_sms_ejecuta_el_temporizador', 'tamatay_social_sms_temporizador' );

	//Envía las notas de cliente por SMS
	function tamatay_social_sms_procesa_notas( $datos ) {
		global $tamatay_social_sms_settings, $wpml_activo;
		
		//Comprobamos si se tiene que enviar el mensaje
		if ( isset( $tamatay_social_sms_settings[ 'mensajes' ] ) && ! array_intersect( [ "todos", "mensaje_nota" ], $tamatay_social_sms_settings[ 'mensajes' ] ) ) {
			return;
		}
	
		//Pedido
		$numero_de_pedido		= $datos[ 'order_id' ];
		$pedido					= new WC_Order( $numero_de_pedido );
		//Recoge datos del formulario de facturación
		$billing_country		= is_callable( [ $pedido, 'get_billing_country' ] ) ? $pedido->get_billing_country() : $pedido->billing_country;
		$billing_phone			= is_callable( [ $pedido, 'get_billing_phone' ] ) ? $pedido->get_billing_phone() : $pedido->billing_phone;
		$shipping_country		= is_callable( [ $pedido, 'get_shipping_country' ] ) ? $pedido->get_shipping_country() : $pedido->shipping_country;	
		$campo_envio			= get_post_meta( $numero_de_pedido, esc_attr( $tamatay_social_sms_settings[ 'campo_envio' ] ), true );
		$telefono				= tamatay_social_sms_procesa_el_telefono( $pedido, $billing_phone, esc_attr( $tamatay_social_sms_settings[ 'servicio' ] ) );
		$telefono_envio			= tamatay_social_sms_procesa_el_telefono( $pedido, $campo_envio, esc_attr( $tamatay_social_sms_settings[ 'servicio' ] ), false, true );
		$enviar_envio			= ( $telefono != $telefono_envio && isset( $tamatay_social_sms_settings[ 'envio' ] ) && $tamatay_social_sms_settings[ 'envio' ] == 1 ) ? true : false;
		$internacional			= ( $billing_country && ( WC()->countries->get_base_country() != $billing_country ) ) ? true : false;
		$internacional_envio	= ( $shipping_country && ( WC()->countries->get_base_country() != $shipping_country ) ) ? true : false;

        //Genera la variable con el texto personalizado
		if ( function_exists( 'icl_register_string' ) || ! $wpml_activo ) { //WPML versión anterior a la 3.2 o no instalado
			$mensaje_nota		= ( $wpml_activo ) ? icl_translate( 'tamatay_social_sms', 'mensaje_nota', esc_textarea( $tamatay_social_sms_settings[ 'mensaje_nota' ] ) ) : esc_textarea( $tamatay_social_sms_settings[ 'mensaje_nota' ] );
		} else if ( $wpml_activo ) { //WPML versión 3.2 o superior
			$mensaje_nota		= apply_filters( 'wpml_translate_single_string', esc_textarea( $tamatay_social_sms_settings[ 'mensaje_nota' ] ), 'tamatay_social_sms', 'mensaje_nota' );
		}
		
		//Cargamos los proveedores SMS
		include_once( 'includes/admin/proveedores.php' );
        
		//Envía el SMS
		if ( ! $internacional || ( isset( $tamatay_social_sms_settings[ 'internacional' ] ) && $tamatay_social_sms_settings[ 'internacional' ] == 1 ) ) {
            $variables  = esc_textarea( $tamatay_social_sms_settings[ 'variables' ] );
			if ( ! is_array( $telefono ) ) {
				tamatay_social_sms_envia_sms( $tamatay_social_sms_settings, $telefono, tamatay_social_sms_procesa_variables( $mensaje_nota, $pedido, $variables, wptexturize( $datos[ 'customer_note' ] ) ), 'mensaje_nota' ); //Mensaje para el teléfono de facturación
			} else {
				foreach ( $telefono as $cliente ) {
					tamatay_social_sms_envia_sms( $tamatay_social_sms_settings, $cliente, tamatay_social_sms_procesa_variables( $mensaje_nota, $pedido, $variables, wptexturize( $datos[ 'customer_note' ] ) ), 'mensaje_nota' ); //Mensaje para los teléfonos recibidos
				}
			}
			if ( $enviar_envio ) {
				tamatay_social_sms_envia_sms( $tamatay_social_sms_settings, $telefono_envio, tamatay_social_sms_procesa_variables( $mensaje_nota, $pedido, $variables, wptexturize( $datos[ 'customer_note' ] ) ), 'mensaje_nota' ); //Mensaje para el teléfono de envío
			}
		}
	}
	add_action( 'woocommerce_new_customer_note', 'tamatay_social_sms_procesa_notas', 10 );
} else {
	add_action( 'admin_notices', 'tamatay_social_sms_requiere_wc' );
}

//Muestra el mensaje de activación de WooCommerce y desactiva el plugin
function tamatay_social_sms_requiere_wc() {
	global $tamatay_social_sms;
		
	echo '<div class="notice notice-error is-dismissible" id="woocommerce-tamatay-social-sms-notifications"><h3>' . $tamatay_social_sms[ 'plugin' ] . '</h3><h4>' . __( "This plugin require WooCommerce active to run!", 'woocommerce-tamatay-social-sms-notifications' ) . '</h4></div>';
	deactivate_plugins( DIRECCION_tamatay_social_sms );
}

//Eliminamos todo rastro del plugin al desinstalarlo
function tamatay_social_sms_desinstalar() {
	delete_option( 'tamatay_social_sms_settings' );
	delete_transient( 'tamatay_social_sms_plugin' );
}
register_uninstall_hook( __FILE__, 'tamatay_social_sms_desinstalar' );
