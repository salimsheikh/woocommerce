<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * WooCommerce Remote Logger
 *
 * The WooCommerce remote logger class adds functionality to log WooCommerce errors remotely based on if the customer opted in and several other conditions.
 *
 * No personal information is logged, only error information and relevant context.
 *
 * @class WC_Remote_Logger
 * @since 9.2.0
 * @package WooCommerce\Classes
 */
class WC_Remote_Logger {
	/**
	 * Determines if remote logging is allowed based on the following conditions:
	 *
	 * 1. The feature flag for remote error logging is enabled.
	 * 2. The user has opted into tracking/logging.
	 * 3. The store is allowed to log based on the variant assignment percentage.
	 * 4. The current WooCommerce version is the latest so we don't log errors that might have been fixed in a newer version.
	 *
	 * @return bool
	 */
	public function is_remote_logging_allowed() {
		if ( ! FeaturesUtil::feature_is_enabled( 'remote_logging' ) ) {
			return false;
		}

		if ( ! $this->is_tracking_opted_in() ) {
			return false;
		}

		if ( ! $this->is_variant_assignment_allowed() ) {
			return false;
		}

		if ( ! $this->is_latest_woocommerce_version() ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the user has opted into tracking/logging.
	 *
	 * @return bool
	 */
	private function is_tracking_opted_in() {
		return 'yes' === get_option( 'woocommerce_allow_tracking', 'no' );
	}

	/**
	 * Check if the store is allowed to log based on the variant assignment percentage.
	 *
	 * @return bool
	 */
	private function is_variant_assignment_allowed() {
		$assignment = get_option( 'woocommerce_remote_variant_assignment', 0 );
		return ( $assignment <= 12 ); // Considering 10% of the 0-120 range.
	}

	/**
	 * Check if the current WooCommerce version is the latest.
	 *
	 * @return bool
	 */
	private function is_latest_woocommerce_version() {
		$latest_wc_version = $this->fetch_latest_woocommerce_version();

		if ( is_null( $latest_wc_version ) ) {
			return false;
		}

		return version_compare( WC()->version, $latest_wc_version, '>=' );
	}

	/**
	 * Fetch the latest WooCommerce version using the WordPress API and cache it.
	 *
	 * @return string|null
	 */
	private function fetch_latest_woocommerce_version() {
		$transient_key  = 'latest_woocommerce_version';
		$cached_version = get_transient( $transient_key );
		if ( $cached_version ) {
			return $cached_version;
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		$plugin_info = plugins_api( 'plugin_information', array( 'slug' => 'woocommerce' ) );
		if ( is_wp_error( $plugin_info ) ) {
			return null;
		}

		if ( ! empty( $plugin_info->version ) ) {
			$latest_version = $plugin_info->version;
			set_transient( $transient_key, $latest_version, DAY_IN_SECONDS );
			return $latest_version;
		}

		return null;
	}
}
