<?php
//Igual no deberías poder abrirme
defined( 'ABSPATH' ) || exit;

global $tamatay_social_sms_settings, $wpml_activo, $mensajes;

//Control de tabulación
$tab    = 1;

//WPML
if ( $tamatay_social_sms_settings ) {    
    foreach ( $mensajes as $mensaje ) {
        if ( function_exists( 'icl_register_string' ) || ! $wpml_activo ) { //Versión anterior a la 3.2
            $mensaje		= ( $wpml_activo ) ? icl_translate( 'tamatay_social_sms', $mensaje, esc_textarea( $tamatay_social_sms_settings[ $mensaje ] ) ) : esc_textarea( $tamatay_social_sms_settings[ $mensaje ] );
        } else if ( $wpml_activo ) { //Versión 3.2 o superior
            $mensaje		= apply_filters( 'wpml_translate_single_string', esc_textarea( $tamatay_social_sms_settings[ $mensaje ] ), 'tamatay_social_sms', $mensaje );
        }
    }
} else { //Inicializa variables
    foreach ( $mensajes as $mensaje ) {
        $mensaje   = '';
    }
}

//Listado de proveedores SMS
$listado_de_proveedores = [      
        "waapi"             => "WhatsApp Notifications - Tamatay.com",
        "msg91"             => "91SMS.in",
];
asort( $listado_de_proveedores, SORT_NATURAL | SORT_FLAG_CASE ); //Ordena alfabeticamente los proveedores

//Campos necesarios para cada proveedor
$campos_de_proveedores      = [
	"msg91" 			=> [ 
		"clave_msg91"                     => __( 'authentication key', 'woocommerce-tamatay-social-sms-notifications' ),
		"identificador_msg91"             => __( 'sender ID', 'woocommerce-tamatay-social-sms-notifications' ),
		"ruta_msg91"                      => __( 'route', 'woocommerce-tamatay-social-sms-notifications' ),
		"dlt_msg91"                       => __( 'template ID', 'woocommerce-tamatay-social-sms-notifications' ),
    ],
	"waapi"             => [
		"usuario_waapi"                   => __( 'Client ID', 'woocommerce-tamatay-social-sms-notifications' ),
		"contrasena_waapi"                => __( 'Instance ID', 'woocommerce-tamatay-social-sms-notifications' ),
	], 
];

//Opciones de campos de selección de los proveedores
$opciones_de_proveedores        = [
	"ruta_msg91"		=> [
		"default"				=> __( 'Default', 'woocommerce-tamatay-social-sms-notifications' ), 
		1						=> 1, 
		4						=> 4,
	],
];

//Campos de verificación
$verificacion_de_proveedores    = [
    "short_sendsms",
    "gdpr_sendsms",
    "dlt_moplet",
    "dlt_msg91",
];

//Listado de estados de pedidos
$listado_de_estados				= wc_get_order_statuses();
$listado_de_estados_temporal	= [];
$estados_originales				= [ 
	'pending',
	'failed',
	'on-hold',
	'processing',
	'completed',
	'refunded',
	'cancelled',
];
foreach ( $listado_de_estados as $clave => $estado ) {
	$nombre_de_estado = str_replace( "wc-", "", $clave );
	if ( ! in_array( $nombre_de_estado, $estados_originales ) ) {
		$listado_de_estados_temporal[ $estado ] = $nombre_de_estado;
	}
}
$listado_de_estados = $listado_de_estados_temporal;

//Listado de mensajes personalizados
$listado_de_mensajes = [
	'todos'					=> __( 'All messages', 'woocommerce-tamatay-social-sms-notifications' ),
	'mensaje_pedido'		=> __( 'Owner custom message', 'woocommerce-tamatay-social-sms-notifications' ),
	'mensaje_pendiente'		=> __( 'Order pending custom message', 'woocommerce-tamatay-social-sms-notifications' ),
	'mensaje_fallido'		=> __( 'Order failed custom message', 'woocommerce-tamatay-social-sms-notifications' ),
	'mensaje_recibido'		=> __( 'Order on-hold custom message', 'woocommerce-tamatay-social-sms-notifications' ),
	'mensaje_procesando'	=> __( 'Order processing custom message', 'woocommerce-tamatay-social-sms-notifications' ),
	'mensaje_completado'	=> __( 'Order completed custom message', 'woocommerce-tamatay-social-sms-notifications' ),
	'mensaje_devuelto'		=> __( 'Order refunded custom message', 'woocommerce-tamatay-social-sms-notifications' ),
	'mensaje_cancelado'		=> __( 'Order cancelled custom message', 'woocommerce-tamatay-social-sms-notifications' ),
	'mensaje_nota'			=> __( 'Notes custom message', 'woocommerce-tamatay-social-sms-notifications' ),
];

/*
Pinta el campo select con el listado de proveedores
*/
function tamatay_social_sms_listado_de_proveedores( $listado_de_proveedores ) {
	global $tamatay_social_sms_settings;
	
	foreach ( $listado_de_proveedores as $valor => $proveedor ) {
		$chequea = ( isset( $tamatay_social_sms_settings[ 'servicio' ] ) && $tamatay_social_sms_settings[ 'servicio' ] == $valor ) ? ' selected="selected"' : '';
		echo '<option value="' . esc_attr( $valor ) . '"' . $chequea . '>' . $proveedor . '</option>' . PHP_EOL;
	}
}

/*
Pinta los campos de los proveedores
*/
function tamatay_social_sms_campos_de_proveedores( $listado_de_proveedores, $campos_de_proveedores, $opciones_de_proveedores, $verificacion_de_proveedores ) {
	global $tamatay_social_sms_settings, $tab;
	
	foreach ( $listado_de_proveedores as $valor => $proveedor ) {
		foreach ( $campos_de_proveedores[$valor] as $valor_campo => $campo ) {
			if ( array_key_exists( $valor_campo, $opciones_de_proveedores ) ) { //Campo select
				echo '
  <tr valign="top" class="' . $valor . '"><!-- ' . $proveedor . ' -->
	<th scope="row" class="titledesc"> <label for="tamatay_social_sms_settings[' . $valor_campo . ']">' .ucfirst( $campo ) . ':' . '
	  <span class="woocommerce-help-tip" data-tip="' . sprintf( __( 'The %s for your account in %s', 'woocommerce-tamatay-social-sms-notifications' ), $campo, $proveedor ) . '"></span></label></th>
	<td class="forminp forminp-number"><select class="wc-enhanced-select" id="tamatay_social_sms_settings[' . $valor_campo . ']" name="tamatay_social_sms_settings[' . $valor_campo . ']" tabindex="' . $tab++ . '">
				';
				foreach ( $opciones_de_proveedores[$valor_campo] as $valor_opcion => $opcion ) {
					$chequea = ( isset( $tamatay_social_sms_settings[$valor_campo] ) && $tamatay_social_sms_settings[$valor_campo] == $valor_opcion ) ? ' selected="selected"' : '';
					echo '<option value="' . esc_attr( $valor_opcion ) . '"' . $chequea . '>' . $opcion . '</option>' . PHP_EOL;
				}
				echo '          </select></td>
  </tr>
				';
			} elseif ( in_array( $valor_campo, $verificacion_de_proveedores ) ) { //Campo checkbox
                $dlt        = ( strpos( $valor_campo, "dlt_" ) !== false ) ? ' class="dlt"' : '';
                $chequea    = ( isset( $tamatay_social_sms_settings[$valor_campo] ) && $tamatay_social_sms_settings[$valor_campo] == 1 ) ? ' checked="checked"' : '';
				echo '
  <tr valign="top" class="' . $valor . '"><!-- ' . $proveedor . ' -->
	<th scope="row" class="titledesc"> <label for="tamatay_social_sms_settings[' . $valor_campo . ']">' . ucfirst( $campo ) . ':' . '
	  <span class="woocommerce-help-tip" data-tip="' . sprintf( __( 'The %s for your account in %s', 'woocommerce-tamatay-social-sms-notifications' ), $campo, $proveedor ) . '"></span></label></th>
	<td class="forminp forminp-number"><input type="checkbox"' . $dlt . ' id="tamatay_social_sms_settings[' . $valor_campo . ']" name="tamatay_social_sms_settings[' . $valor_campo . ']" value="1"' . $chequea . ' tabindex="' . $tab++ . '" ></td>
  </tr>
				';
            } else { //Campo input
				echo '
  <tr valign="top" class="' . $valor . '"><!-- ' . $proveedor . ' -->
	<th scope="row" class="titledesc"> <label for="tamatay_social_sms_settings[' . $valor_campo . ']">' . ucfirst( $campo ) . ':' . '
	  <span class="woocommerce-help-tip" data-tip="' . sprintf( __( 'The %s for your account in %s', 'woocommerce-tamatay-social-sms-notifications' ), $campo, $proveedor ) . '"></span></label></th>
	<td class="forminp forminp-number"><input type="text" id="tamatay_social_sms_settings[' . $valor_campo . ']" name="tamatay_social_sms_settings[' . $valor_campo . ']" size="50" value="' . ( isset( $tamatay_social_sms_settings[$valor_campo] ) ? esc_attr( $tamatay_social_sms_settings[$valor_campo] ) : '' ) . '" tabindex="' . $tab++ . '" /></td>
  </tr>
				';
			}
		}
	}
}

/*
Pinta los campos del formulario de envío
*/
function tamatay_social_sms_campos_de_envio() {
	global $tamatay_social_sms_settings;

	$pais					= new WC_Countries();
	$campos					= $pais->get_address_fields( $pais->get_base_country(), 'shipping_' ); //Campos ordinarios
	$campos_personalizados	= apply_filters( 'woocommerce_checkout_fields', [] );
	if ( isset( $campos_personalizados[ 'shipping' ] ) ) {
		$campos += $campos_personalizados[ 'shipping' ];
	}
	foreach ( $campos as $valor => $campo ) {
		$chequea = ( isset( $tamatay_social_sms_settings[ 'campo_envio' ] ) && $tamatay_social_sms_settings[ 'campo_envio' ] == $valor ) ? ' selected="selected"' : '';
		if ( isset( $campo[ 'label' ] ) ) {
			echo '<option value="' . esc_attr( $valor ) . '"' . $chequea . '>' . $campo[ 'label' ] . '</option>' . PHP_EOL;
		}
	}
}

/*
Pinta el campo select con el listado de estados de pedido
*/
function tamatay_social_sms_listado_de_estados( $listado_de_estados ) {
	global $tamatay_social_sms_settings;

	foreach( $listado_de_estados as $nombre_de_estado => $estado ) {
		$chequea = '';
		if ( isset( $tamatay_social_sms_settings[ 'estados_personalizados' ] ) ) {
			foreach ( $tamatay_social_sms_settings[ 'estados_personalizados' ] as $estado_personalizado ) {
				if ( $estado_personalizado == $estado ) {
					$chequea = ' selected="selected"';
				}
			}
		}
		echo '<option value="' . esc_attr( $estado ) . '"' . $chequea . '>' . $nombre_de_estado . '</option>' . PHP_EOL;
	}
}

/*
Pinta el campo select con el listado de mensajes personalizados
*/
function tamatay_social_sms_listado_de_mensajes( $listado_de_mensajes ) {
	global $tamatay_social_sms_settings;
	
	$chequeado = false;
	foreach ( $listado_de_mensajes as $valor => $mensaje ) {
		if ( isset( $tamatay_social_sms_settings[ 'mensajes' ] ) && in_array( $valor, $tamatay_social_sms_settings[ 'mensajes' ] ) ) {
			$chequea	= ' selected="selected"';
			$chequeado	= true;
		} else {
			$chequea	= '';
		}
		$texto = ( ! isset( $tamatay_social_sms_settings[ 'mensajes' ] ) && $valor == 'todos' && ! $chequeado ) ? ' selected="selected"' : '';
		echo '<option value="' . esc_attr( $valor ) . '"' . $chequea . $texto . '>' . $mensaje . '</option>' . PHP_EOL;
	}
}

/*
Pinta los campos de mensajes
*/
function tamatay_social_sms_campo_de_mensaje_personalizado( $campo, $campo_cliente, $listado_de_mensajes ) {
    global $tab, $tamatay_social_sms_settings;
    
    //Listado de mensajes personalizados
    $listado_de_mensajes_personalizados = [
        'mensaje_pedido'		=> __( 'Order No. %s received on ', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_pendiente'		=> __( 'Thank you for shopping with us! Your order No. %s is now: ', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_fallido'		=> __( 'Thank you for shopping with us! Your order No. %s is now: ', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_recibido'		=> __( 'Your order No. %s is received on %s. Thank you for shopping with us!', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_procesando'	=> __( 'Thank you for shopping with us! Your order No. %s is now: ', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_completado'	=> __( 'Thank you for shopping with us! Your order No. %s is now: ', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_devuelto'		=> __( 'Thank you for shopping with us! Your order No. %s is now: ', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_cancelado'		=> __( 'Thank you for shopping with us! Your order No. %s is now: ', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_nota'			=> __( 'A note has just been added to your order No. %s: ', 'woocommerce-tamatay-social-sms-notifications' ),
    ];

    //Listado de textos personalizados
    $listado_de_textos_personalizados = [
        'mensaje_pendiente'		=> __( 'Pending', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_fallido'		=> __( 'Failed', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_procesando'	=> __( 'Processing', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_completado'	=> __( 'Completed', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_devuelto'		=> __( 'Refunded', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_cancelado'		=> __( 'Cancelled', 'woocommerce-tamatay-social-sms-notifications' ),
    ];

    if ( $campo == 'mensaje_pedido'  ) {
        $texto  = stripcslashes( ! empty( $campo_cliente ) ? $campo_cliente : sprintf( __( $listado_de_mensajes_personalizados[ $campo ], 'woocommerce-tamatay-social-sms-notifications' ), "%id%" ) . "%shop_name%" . "." );
    } elseif ( $campo == 'mensaje_recibido'  ) {
        $texto  = stripcslashes( ! empty( $campo_cliente ) ? $campo_cliente : sprintf( __( $listado_de_mensajes_personalizados[ $campo ], 'woocommerce-tamatay-social-sms-notifications' ), "%id%", "%shop_name%" ) );
    } elseif ( $campo == 'mensaje_nota'  ) {
        $texto  = stripcslashes( ! empty( $campo_cliente ) ? $campo_cliente : sprintf( __( $listado_de_mensajes_personalizados[ $campo ], 'woocommerce-tamatay-social-sms-notifications' ), "%id%" ) . "%note%" );
    } else {
        $texto  = stripcslashes( ! empty( $campo_cliente ) ? $campo_cliente : sprintf( __( $listado_de_mensajes_personalizados[ $campo ], 'woocommerce-tamatay-social-sms-notifications' ), "%id%" ) . __( $listado_de_textos_personalizados[ $campo ], 'woocommerce-tamatay-social-sms-notifications' ) . "." );
    }
    
    //Listado de mensajes personalizados - DLT
    $listado_de_mensajes_dlt = [
        'mensaje_pedido'		=> __( 'Owner custom message template ID', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_pendiente'		=> __( 'Order pending custom message template ID', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_fallido'		=> __( 'Order failed custom message template ID', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_recibido'		=> __( 'Order on-hold custom message template ID', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_procesando'	=> __( 'Order processing custom message template ID', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_completado'	=> __( 'Order completed custom message template ID', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_devuelto'		=> __( 'Order refunded custom message template ID', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_cancelado'		=> __( 'Order cancelled custom message template ID', 'woocommerce-tamatay-social-sms-notifications' ),
        'mensaje_nota'			=> __( 'Notes custom message template ID', 'woocommerce-tamatay-social-sms-notifications' ),
    ];
    
    $texto_dlt  = ( isset( $tamatay_social_sms_settings[ 'dlt_' . $campo ] ) ) ? $tamatay_social_sms_settings[ 'dlt_' . $campo ] : '';
    
    echo '
        <tr valign="top" class="' . $campo . '">
            <th scope="row" class="titledesc">
                <label for="tamatay_social_sms_settings[' . $campo . ']">
                    ' . __( $listado_de_mensajes[ $campo ], 'woocommerce-tamatay-social-sms-notifications' ) .':
                    <span class="woocommerce-help-tip" data-tip="'. __( "You can customize your message. Remember that you can use this variables: %id%, %order_key%, %billing_first_name%, %billing_last_name%, %billing_company%, %billing_address_1%, %billing_address_2%, %billing_city%, %billing_postcode%, %billing_country%, %billing_state%, %billing_email%, %billing_phone%, %shipping_first_name%, %shipping_last_name%, %shipping_company%, %shipping_address_1%, %shipping_address_2%, %shipping_city%, %shipping_postcode%, %shipping_country%, %shipping_state%, %shipping_method%, %shipping_method_title%, %payment_method%, %payment_method_title%, %order_discount%, %cart_discount%, %order_tax%, %order_shipping%, %order_shipping_tax%, %order_total%, %status%, %prices_include_tax%, %tax_display_cart%, %display_totals_ex_tax%, %display_cart_ex_tax%, %order_date%, %modified_date%, %customer_message%, %customer_note%, %post_status%, %shop_name%, %order_product% and %note%.", "woocommerce-tamatay-social-sms-notifications" ) . '"></span>
                </label>
            </th>
            <td class="forminp forminp-number"><textarea id="tamatay_social_sms_settings[' . $campo . ']" name="tamatay_social_sms_settings[' . $campo . ']" cols="50" rows="5" tabindex="' . $tab++ . '">' . esc_textarea( $texto ) . '</textarea>
            </td>
        </tr>
        <tr valign="top" class="mensaje_dlt dlt_' . $campo . '">
            <th scope="row" class="titledesc">
                <label for="tamatay_social_sms_settings[dlt_' . $campo . ']">
                    ' . __( $listado_de_mensajes_dlt[ $campo ], 'woocommerce-tamatay-social-sms-notifications' ) .':
                    <span class="woocommerce-help-tip" data-tip="'. __( "Template ID for " . $listado_de_mensajes[ $campo ] ) . '"></span>
                </label>
            </th>
            <td class="forminp forminp-number"><input type="text" id="tamatay_social_sms_settings[dlt_' . $campo . ']" name="tamatay_social_sms_settings[dlt_' . $campo . ']" size="50" value="' . esc_attr( $texto_dlt ) . '" tabindex="' . $tab++ . '"/>
            </td>
        </tr>';
}
