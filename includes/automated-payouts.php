<?php
if( edd_get_option( 'edd_commissions_autopay_pa' ) ){
	
	add_filter('epap_adaptive_receivers', 'eddc_paypal_adaptive_autopay', 8, 2);
	add_action('eddc_insert_commission', 'eddc_override_commission_status', 8, 6);
	
	function eddc_paypal_adaptive_autopay($receivers, $payment) {
		$data  = edd_get_payment_meta( $payment );
		$cart  = edd_get_payment_meta_cart_details( $payment )
		$total = edd_get_payment_amount($payment);
		$data  = edd_get_payment_meta_downloads($payment);
		$final = array();
		foreach ($data as $val) {
			$recipients = eddc_get_recipients($val['id']);
			foreach ($recipients as $recipient) {
				$type   = eddc_get_commission_type($val['id']);
				$amount = 0;
				if ($type === 'percentage') {
					// get percentage of cart is product total
					$poc    = edd_get_percentage_of_cart($val['id'], $cart, $payment);
					// get percentage of product is user total
					$amount = $amount + $poc * (eddc_get_recipient_rate($val['id'], $recipient)) / 100;
				} else if ($type === 'flat') {
					$amount = $amount + (eddc_get_recipient_rate($val['id'], $recipient) / $total) * 100;
				} else {
					// no money if not flat or percentage	
				}
				$paypal = get_user_meta($recipient, 'eddc_user_paypal', true);
				if ($amount === 0) {
					// no money, no record in array
				} elseif (isset($final[$paypal])) {
					$final[$paypal] = $amount + $final[$paypal];
				} else {
					$final[$paypal] = $amount;
				}
			}
		}
		$return  = '';
		$counter = 0;
		$taken   = 0;
		foreach ($final as $person => $val) {
			$taken = $taken + $val;
		}
		$remaining = 100 - $taken;
		$owner     = $receivers;
		$owner     = explode("\n", $owner);
		foreach ($owner as $key => $val) {
			$val       = explode('|', $val);
			$email     = $val[0];
			$pfg       = $val[1];
			$remainder = ($pfg / 100) * $remaining;
			if (isset($final[$email])) {
				$final[$email] = $final[$email] + $remainder;
			} else {
				$final[$email] = $remainder;
			}
		}
		foreach ($final as $person => $val) {
			if ($counter === 0) {
				$return = $person . "|" . $val;
			} else {
				$return = $return . "\n" . $person . "|" . $val;
			}
			$counter++;
		}
		return $return;
	}
	function edd_get_percentage_of_cart($id, $data, $payment) {
		$total   = 0;
		$price   = 0;
		$overall = edd_get_payment_amount($payment);
		foreach ($data as $val) {
			if ($id == $val['id']) {
				$price = $val['quantity'] * $val['price'];
			}
			$total = $total + ($val['quantity'] * $val['price']);
		}
		$aot     = ($total / $overall) * $price;
		$percent = ($price / $overall) * 100;
		return $percent;
	}
	
	function eddc_override_commission_status($recipient, $commission_amount, $rate, $download_id, $commission_id, $payment_id) {
		update_post_meta($commission_id, '_commission_status', 'paid');
	}
	
}