<?php
/**
 * Download Freemius update packages without relaxing the API transport policy.
 *
 * @package FooGallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'FooGallery_Freemius_Recovery_Downloader' ) ) {
	/**
	 * Handle package redirects for every product affected by the host-wide guard.
	 *
	 * Kept separate from the guard so a fixed edition can coexist with an older
	 * edition that has already declared the original transport-guard class.
	 */
	final class FooGallery_Freemius_Recovery_Downloader {
		/** Maximum redirect responses followed for one package. */
		const MAX_REDIRECTS = 3;

		/** Register after product-specific download handlers. */
		public static function register() {
			add_filter( 'upgrader_pre_download', array( __CLASS__, 'download_package' ), 20, 4 );
		}

		/**
		 * Recognize SDK package endpoints without assuming a particular product ID.
		 *
		 * @param mixed $url Package URL.
		 * @return bool
		 */
		private static function is_package_url( $url ) {
			$parts = is_string( $url ) ? wp_parse_url( $url ) : false;
			if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['path'] ) ) {
				return false;
			}
			$hosts = array( 'api.freemius.com', 'sandbox-api.freemius.com', 'wp.freemius.com' );
			if ( defined( 'WP_FS__ADDRESS' ) && is_string( WP_FS__ADDRESS ) ) {
				$address = 0 === strpos( WP_FS__ADDRESS, '://' ) ? 'https' . WP_FS__ADDRESS : WP_FS__ADDRESS;
				$host    = wp_parse_url( $address, PHP_URL_HOST );
				if ( is_string( $host ) && '' !== $host ) {
					$hosts[] = strtolower( rtrim( $host, '.' ) );
				}
			}
			return in_array( strtolower( rtrim( $parts['host'], '.' ) ), $hosts, true )
				&& 1 === preg_match( '~\A/v1/installs/[1-9][0-9]*/updates/[1-9][0-9]*\.zip\z~', $parts['path'] );
		}

		/**
		 * Validate both the initial URL and each storage destination.
		 *
		 * @param mixed $url Absolute URL.
		 * @return bool
		 */
		private static function safe_url( $url ) {
			if ( ! is_string( $url ) || ! preg_match( '~\Ahttps://[a-z0-9.-]+(?::443)?(?:/|\?|\z)~i', $url ) || preg_match( '/[\x00-\x20\\\\]/', $url ) ) {
				return false;
			}
			$parts = wp_parse_url( $url );
			return is_array( $parts ) && ! isset( $parts['user'] ) && ! isset( $parts['pass'] ) && ! isset( $parts['fragment'] )
				&& false !== wp_http_validate_url( $url );
		}

		/**
		 * Resolve a Location without inheriting the original authorization query.
		 *
		 * @param mixed  $location Location header.
		 * @param string $url      Previous URL.
		 * @return string|false
		 */
		private static function redirect_url( $location, $url ) {
			if ( ! is_string( $location ) || '' === $location || preg_match( '/[\x00-\x20\\\\]/', $location ) ) {
				return false;
			}
			$parts = wp_parse_url( $location );
			// Check before resolving: make_absolute_url() omits relative authority userinfo.
			if ( ! is_array( $parts ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['fragment'] ) ) {
				return false;
			}
			$target = WP_Http::make_absolute_url( $location, $url );
			return self::safe_url( $target ) ? $target : false;
		}

		/**
		 * Stream one hop, failing closed if HTTP filters weaken its transport.
		 *
		 * No request headers, cookies or response cookies are reused between hops.
		 * Authentication belongs to the SDK URL; storage uses its own signed URL.
		 *
		 * @param string $url      Validated URL.
		 * @param string $filename Temporary destination.
		 * @return array|WP_Error
		 */
		private static function request( $url, $filename ) {
			$validate = static function ( $preempt, $args, $request_url ) use ( $url, $filename ) {
				if ( $url !== $request_url ) {
					return $preempt;
				}
				if ( ! isset( $args['sslverify'], $args['reject_unsafe_urls'], $args['redirection'], $args['stream'], $args['filename'], $args['method'], $args['blocking'], $args['headers'] ) || true !== $args['sslverify'] || true !== $args['reject_unsafe_urls'] || 0 !== $args['redirection'] || true !== $args['stream'] || true !== $args['blocking'] || $filename !== $args['filename'] || 'GET' !== $args['method'] || ! empty( $args['body'] ) || ! empty( $args['cookies'] ) || ( ! is_array( $args['headers'] ) && ! $args['headers'] instanceof Traversable ) ) {
					return new WP_Error( 'foogallery_package_transport', __( 'The package download requires a safe, verified HTTPS connection.', 'foogallery' ) );
				}
				foreach ( $args['headers'] as $name => $value ) {
					if ( in_array( strtolower( $name ), array( 'authorization', 'proxy-authorization', 'cookie', 'referer', 'host' ), true ) ) {
						return new WP_Error( 'foogallery_package_transport', __( 'The package download cannot forward authentication headers or cookies.', 'foogallery' ) );
					}
				}
				return $preempt;
			};
			add_filter( 'pre_http_request', $validate, PHP_INT_MAX, 3 );
			try {
				return wp_safe_remote_get(
					$url,
					array(
						'timeout'     => 300,
						'sslverify'   => true,
						'redirection' => 0,
						'stream'      => true,
						'filename'    => $filename,
						'headers'     => array(),
						'cookies'     => array(),
					)
				);
			} finally {
				remove_filter( 'pre_http_request', $validate, PHP_INT_MAX );
			}
		}

		/**
		 * Follow only validated package redirects, leaving ordinary API calls alone.
		 *
		 * The upgrader owns authorization, archive validation and installation. Its
		 * package URL comes from update metadata, not a new user-facing endpoint.
		 *
		 * @param false|string|WP_Error $reply      Previous download result.
		 * @param string                $package    SDK package URL.
		 * @param WP_Upgrader           $upgrader   Current upgrader.
		 * @param array                 $hook_extra Upgrader type and action.
		 * @return false|string|WP_Error
		 */
		public static function download_package( $reply, $package, $upgrader, $hook_extra ) {
			if ( false !== $reply || ! is_array( $hook_extra ) ) {
				return $reply;
			}
			$type = $upgrader instanceof Plugin_Upgrader ? 'plugin' : ( $upgrader instanceof Theme_Upgrader ? 'theme' : '' );
			if ( '' === $type || ( isset( $hook_extra['type'] ) && $type !== $hook_extra['type'] ) || ( isset( $hook_extra['action'] ) && ! in_array( $hook_extra['action'], array( 'install', 'update' ), true ) ) ) {
				return $reply;
			}
			// Core bulk updaters pass only the plugin/theme identity, without type/action.
			if ( ( ! isset( $hook_extra['type'], $hook_extra['action'] ) && empty( $hook_extra[ $type ] ) ) || ! self::is_package_url( $package ) ) {
				return $reply;
			}
			if ( ! self::safe_url( $package ) ) {
				return new WP_Error( 'foogallery_package_url', __( 'The package download URL is not safe.', 'foogallery' ) );
			}

			$url  = $package;
			$seen = array();
			for ( $hop = 0; $hop <= self::MAX_REDIRECTS; $hop++ ) {
				$seen[] = $url;
				$temp   = wp_tempnam( 'foogallery-freemius.zip' );
				if ( ! $temp ) {
					return new WP_Error( 'foogallery_package_temp', __( 'Could not create a temporary package file.', 'foogallery' ) );
				}
				$keep = false;
				try {
					$response = self::request( $url, $temp );
					if ( is_wp_error( $response ) ) {
						// Transport messages can contain signed URLs; do not expose credentials.
						return new WP_Error( 'foogallery_package_download', __( 'The package could not be downloaded over a verified HTTPS connection.', 'foogallery' ) );
					}
					$status = wp_remote_retrieve_response_code( $response );
					if ( 200 === $status ) {
						clearstatcache( true, $temp );
						if ( ! file_exists( $temp ) || 0 === filesize( $temp ) ) {
							return new WP_Error( 'foogallery_package_empty', __( 'The downloaded package is empty.', 'foogallery' ) );
						}
						$keep = true;
						return $temp;
					}
					if ( in_array( $status, array( 301, 302, 303, 307, 308 ), true ) && $hop < self::MAX_REDIRECTS ) {
						$target = self::redirect_url( wp_remote_retrieve_header( $response, 'location' ), $url );
						if ( false !== $target && ! in_array( $target, $seen, true ) ) {
							$url = $target;
							continue;
						}
					}
					return new WP_Error(
						'foogallery_package_response',
						/* translators: %d: HTTP response status code. */
						sprintf( __( 'The package server returned an unsuccessful or unsafe response (HTTP %d).', 'foogallery' ), $status )
					);
				} finally {
					if ( ! $keep ) {
						wp_delete_file( $temp );
					}
				}
			}
		}
	}
}

FooGallery_Freemius_Recovery_Downloader::register();
