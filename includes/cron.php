<?php
add_filter('cron_schedules', 'custom_cron_intervals');
add_action('edd_settings_extensions_sanitize', 'eddc_check_schedule');

function eddc_check_schedule($input) {
	global $edd_options;
	$old_interval = wp_get_schedule('eddc_schedule_mass_payments');
	$new_interval = $input['edd_commissions_autopay_schedule'];
	$instapay     = $edd_options['edd_commissions_autopay_pa'];
	
	/**
	 * 1. The user actually changed the schedule
	 * 2. Instapay is turned off
	 * 3. Manual was not selected
	 */
	if (($old_interval != $new_interval) && !$instapay && $new_interval != 'manual') {
		eddc_remove_cron_schedule($options);
		eddc_schedule_cron($new_interval);
	}
	
	if ($new_interval == 'manual' || $instapay) {
		eddc_remove_cron_schedule($options);
	}
	
}

function pay_now() {
	$mass_pay = new PV_Mass_Pay;
	$mass_pay = $mass_pay->do_payments();
}

function eddc_remove_cron_schedule() {
	$timestamp = wp_next_scheduled('eddc_schedule_mass_payments');
	
	return wp_unschedule_event($timestamp, 'eddc_schedule_mass_payments');
}

function eddc_schedule_cron($interval) {
	// Scheduled event
	add_action('eddc_schedule_mass_payments', 'pay_now');
	
	// Schedule the event
	if (!wp_next_scheduled('eddc_schedule_mass_payments')) {
		wp_schedule_event(time(), $interval, 'eddc_schedule_mass_payments');
		
		return true;
	}
	
	return false;
}

function custom_cron_intervals($schedules) {
	$schedules['weekly'] = array(
		'interval' => 604800,
		'display' => __('Once Weekly')
	);
	
	$schedules['biweekly'] = array(
		'interval' => 1209600,
		'display' => __('Once every two weeks')
	);
	
	$schedules['monthly'] = array(
		'interval' => 2635200,
		'display' => __('Once a month')
	);
	
	return $schedules;
}