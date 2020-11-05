<?php

add_action( 'rcl_payments_gateway_init', 'rcl_add_enot_gateway' );
function rcl_add_enot_gateway() {
	rcl_gateway_register( 'enot', 'Rcl_Enot_Payment' );
}

class Rcl_Enot_Payment extends Rcl_Gateway_Core {
	function __construct() {
		parent::__construct( array(
			'request'	 => 'enot-request',
			'name'		 => rcl_get_commerce_option( 'enot_custom_name', 'Enot' ),
			'submit'	 => __( 'Оплатить через Enot' ),
			'icon'		 => rcl_addon_url( 'icon.jpg', __FILE__ )
		) );
	}

	function get_options() {

		return array(
			array(
				'type'			 => 'text',
				'slug'			 => 'enot_custom_name',
				'title'			 => __( 'Наименование платежной системы' ),
				'placeholder'	 => 'Enot'
			),
			array(
				'type'	 => 'number',
				'slug'	 => 'enot_id',
				'title'	 => __( 'ID магазина' )
			),
			array(
				'type'	 => 'password',
				'slug'	 => 'enot_secret_key',
				'title'	 => __( 'Секретный пароль' )
			),
			array(
				'type'	 => 'password',
				'slug'	 => 'enot_other_key',
				'title'	 => __( 'Дополнительный ключ' )
			)
		);
	}

	function get_form( $data ) {

		return parent::construct_form( [
				'action' => "https://enot.io/pay",
				'method' => 'get',
				'fields' => array(
					'm'				 => rcl_get_commerce_option( 'enot_id' ),
					'oa'			 => $data->pay_summ,
					'o'				 => $data->pay_id,
					's'				 => md5( implode( ':', [
						rcl_get_commerce_option( 'enot_id' ),
						$data->pay_summ,
						rcl_get_commerce_option( 'enot_secret_key' ),
						$data->pay_id
					] ) ),
					'cr'			 => $data->currency,
					'c'				 => $data->description,
					'success_url'	 => get_permalink( $data->page_successfully ),
					'fail_url'		 => get_permalink( $data->page_fail ),
					'cf[user_id]'	 => $data->user_id,
					'cf[pay_type]'	 => $data->pay_type,
					'cf[baggage]'	 => $data->baggage_data,
				)
			] );
	}

	function result( $data ) {

		$sign = md5( implode( ':', [
			$_REQUEST['merchant'],
			$_REQUEST['amount'],
			rcl_get_commerce_option( 'enot_other_key' ),
			$_REQUEST["merchant_id"]
			] ) );

		if ( $sign != $_REQUEST['sign_2'] ) {
			rcl_add_log( 'enot-sign-error', $_REQUEST, 1 );
			rcl_mail_payment_error( $sign );
			exit;
		}

		if ( ! parent::get_payment( $_REQUEST['merchant_id'] ) ) {
			parent::insert_payment( array(
				'pay_id'		 => $_REQUEST['merchant_id'],
				'pay_summ'		 => $_REQUEST['amount'],
				'user_id'		 => $_REQUEST["custom_field"]['user_id'],
				'pay_type'		 => $_REQUEST["custom_field"]['pay_type'],
				'baggage_data'	 => $_REQUEST["custom_field"]['baggage']
			) );
		}

		echo "Good";
		exit;
	}

}
