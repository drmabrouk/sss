<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Member_Files_Logger {

	public static function log( $user_id, $action, $details = '' ) {
		$logs = get_user_meta( $user_id, 'mf_activity_log', true ) ?: array();
		$logs[] = array(
			'time'    => time(),
			'action'  => $action,
			'details' => $details,
			'ip'      => $_SERVER['REMOTE_ADDR'],
		);
		// Keep only last 50 logs
		if ( count( $logs ) > 50 ) {
			array_shift( $logs );
		}
		update_user_meta( $user_id, 'mf_activity_log', $logs );
        update_user_meta( $user_id, 'mf_last_activity', time() );
	}

	public static function get_logs( $user_id ) {
		return get_user_meta( $user_id, 'mf_activity_log', true ) ?: array();
	}
}
