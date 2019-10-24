<?php
/*
* Woocommerce Custom Order Status Create & Mail Template 
* Tested Woocommerce Version: 3.7.0
*
*
*
*/


// New order status AFTER woo 2.2
add_action( 'init', 'register_my_new_order_statuses' );

function register_my_new_order_statuses() {
    register_post_status( 'wc-on-review', array(
        'label'                     => _x( 'In-Review', 'Order status', 'woocommerce' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'In-Review <span class="count">(%s)</span>', 'In-Review<span class="count">(%s)</span>', 'woocommerce' )
    ) );
    register_post_status( 'wc-shipping', array(
        'label'                     => _x( 'Shipping In-Process', 'Order status', 'woocommerce' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Shipping In-Process <span class="count">(%s)</span>', 'Shipping In-Process<span class="count">(%s)</span>', 'woocommerce' )
    ) );
    register_post_status( 'wc-service', array(
        'label'                     => _x( 'In-Service', 'Order status', 'woocommerce' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'In-Service <span class="count">(%s)</span>', 'In-Service<span class="count">(%s)</span>', 'woocommerce' )
    ) );
}

add_filter( 'wc_order_statuses', 'my_new_wc_order_statuses' );

// Register in wc_order_statuses.
function my_new_wc_order_statuses( $order_statuses ) {
    $order_statuses['wc-on-review'] = _x( 'In-Review', 'Order status', 'woocommerce' );
    $order_statuses['wc-shipping'] = _x( 'Shipping In-Process', 'Order status', 'woocommerce' );
    $order_statuses['wc-service'] = _x( 'In-Service', 'Order status', 'woocommerce' );

    return $order_statuses;
}

// Admin reports for custom order status
function wc_reports_get_order_custom_report_data_args( $args ) {
    $args['order_status'] = array( 'completed', 'processing', 'on-hold', 'on-review', 'shipping', 'service' );
    return $args;
};
add_filter( 'woocommerce_reports_get_order_report_data_args', 'wc_reports_get_order_custom_report_data_args');

//Add a WooCommerce order status (completed, refunded) into the Dashboard status widget
function woocommerce_add_order_status_dashboard_widget() {
	if ( ! current_user_can( 'edit_shop_orders' ) ) {
		return;
	}
	$shipped_count  = 0;
	$completed_count = 0;
	$review_count  = 0;
	$service_count = 0;

	foreach ( wc_get_order_types( 'order-count' ) as $type ) {
		$counts            = (array) wp_count_posts( $type );
		$shipped_count    += isset( $counts['wc-shipping'] ) ? $counts['wc-shipping'] : 0;
		$completed_count += isset( $counts['wc-completed'] ) ? $counts['wc-completed'] : 0;
		$review_count    += isset( $counts['wc-on-review'] ) ? $counts['wc-on-review'] : 0;
		$service_count += isset( $counts['wc-service'] ) ? $counts['wc-service'] : 0;
	}
	?>
	<li class="review-orders">
	<a href="<?php echo admin_url( 'edit.php?post_status=wc-on-review&post_type=shop_order' ); ?>">
		<?php
			/* translators: %s: order count */
			printf(
				_n( '<strong>%s order</strong> In Review', '<strong>%s orders</strong> In Review', $review_count, 'woocommerce' ),
				$review_count
			);
		?>
		</a>
	</li>
	<li class="inservice-orders">
	<a href="<?php echo admin_url( 'edit.php?post_status=wc-service&post_type=shop_order' ); ?>">
		<?php
			/* translators: %s: order count */
			printf(
				_n( '<strong>%s order</strong> In Service', '<strong>%s orders</strong> In Service', $service_count, 'woocommerce' ),
				$service_count
			);
		?>
		</a>
	</li>
	<li class="shipped-orders">
	<a href="<?php echo admin_url( 'edit.php?post_status=wc-shipping&post_type=shop_order' ); ?>">
		<?php
			/* translators: %s: order count */
			printf(
				_n( '<strong>%s order</strong> Shipping in process', '<strong>%s orders</strong> Shipping in process', $shipped_count, 'woocommerce' ),
				$shipped_count
			);
		?>
		</a>
	</li>
	<li class="completed-orders">
	<a href="<?php echo admin_url( 'edit.php?post_status=wc-completed&post_type=shop_order' ); ?>">
		<?php
			/* translators: %s: order count */
			printf(
				_n( '<strong>%s order</strong> completed', '<strong>%s orders</strong> completed', $completed_count, 'woocommerce' ),
				$completed_count
			);
		?>
		</a>
	</li>
	<?php
}
add_action( 'woocommerce_after_dashboard_status_widget', 'woocommerce_add_order_status_dashboard_widget' );
function my_awesome_shipping_notification( $order_id, $checkout=null ) {
   global $woocommerce;

   $order = new WC_Order( $order_id );

   //error_log( $order->status );

   if($order->status === 'shipping' ) {

      // Create a mailer
      $mailer 		= $woocommerce->mailer();

      $message_body = __( 'Your order has been forwarded to the Shipping Department to initiate shipmnet for you. <br> You will be updated with courier tracking details soon via email.', 'woocommerce'  );

      $message 		= $mailer->wrap_message(
        // Message head and message body.
        sprintf( __( 'Order #%s Shipping In Process', 'woocommerce'  ), $order->get_order_number() ), $message_body );

      // Client email, email subject and message.
		$result = $mailer->send( $order->billing_email, sprintf( __( 'Order #%s Shipping In Process', 'woocommerce'  ), $order->get_order_number() ), $message );

	 //error_log( $result );
	}

}
add_action( 'woocommerce_order_status_changed', 'my_awesome_shipping_notification');
function my_awesome_review_notification( $order_id, $checkout=null ) {
   global $woocommerce;

   $order = new WC_Order( $order_id );

   //error_log( $order->status );

   if($order->status === 'on-review' ) {

      // Create a mailer
      $mailer 		= $woocommerce->mailer();

      $message_body = __( 'Your Order is under review with review team.<br/>Order status will be updated as soon as review gets completed and you will be notified for the same via email.', 'text_domain'  );

      $message 		= $mailer->wrap_message(
        // Message head and message body.
        sprintf( __( 'Order #%s In Review', 'text_domain'  ), $order->get_order_number() ), $message_body );

      // Client email, email subject and message.
		$result = $mailer->send( $order->billing_email, sprintf( __( 'Order #%s In Review', 'text_domain'  ), $order->get_order_number() ), $message );

	 //error_log( $result );
	}

}
add_action( 'woocommerce_order_status_changed', 'my_awesome_review_notification');
function my_awesome_service_notification( $order_id, $checkout=null ) {
   global $woocommerce;

   $order = new WC_Order( $order_id );

   //error_log( $order->status );

   if($order->status === 'service' ) {

      // Create a mailer
      $mailer 		= $woocommerce->mailer();

      $message_body = __( 'Your Product is under service in our service center.<br/>Normally service time will take 1-2 weeks to get it completed properly.<br/>Please co-operate us during that time.', 'text_domain'  );

      $message 		= $mailer->wrap_message(
        // Message head and message body.
        sprintf( __( 'Order #%s In Service', 'text_domain'  ), $order->get_order_number() ), $message_body );

      // Client email, email subject and message.
		$result = $mailer->send( $order->billing_email, sprintf( __( 'Order #%s In Service', 'text_domain'  ), $order->get_order_number() ), $message );

	 //error_log( $result );
	}

}
add_action( 'woocommerce_order_status_changed', 'my_awesome_service_notification');

add_action('woocommerce_order_status_changed', 'send_custom_email_notifications', 10, 4 );
function send_custom_email_notifications( $order_id, $old_status, $new_status, $order ){
    if ( $new_status == 'cancelled' || $new_status == 'failed' ){
        $wc_emails = WC()->mailer()->get_emails(); // Get all WC_emails objects instances
        $customer_email = $order->get_billing_email(); // The customer email
    }

    if ( $new_status == 'cancelled' ) {
        // change the recipient of this instance
        $wc_emails['WC_Email_Cancelled_Order']->recipient = $customer_email;
        // Sending the email from this instance
        $wc_emails['WC_Email_Cancelled_Order']->trigger( $order_id );
    } 
    elseif ( $new_status == 'failed' ) {
        // change the recipient of this instance
        $wc_emails['WC_Email_Failed_Order']->recipient = $customer_email;
        // Sending the email from this instance
        $wc_emails['WC_Email_Failed_Order']->trigger( $order_id );
    } 
}
