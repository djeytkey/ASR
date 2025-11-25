<?php
/**
 * WordPress Update Checker for GitHub
 *
 * @package Almokhlif_Oud_Sales_Report
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Almokhlif_Oud_Sales_Report_Update_Checker {
	
	/**
	 * GitHub repository owner
	 *
	 * @var string
	 */
	private $github_owner;
	
	/**
	 * GitHub repository name
	 *
	 * @var string
	 */
	private $github_repo;
	
	/**
	 * GitHub access token (optional, for private repos)
	 *
	 * @var string
	 */
	private $github_token;
	
	/**
	 * Plugin slug
	 *
	 * @var string
	 */
	private $plugin_slug;
	
	/**
	 * Plugin file path
	 *
	 * @var string
	 */
	private $plugin_file;
	
	/**
	 * Current plugin version
	 *
	 * @var string
	 */
	private $current_version;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->plugin_slug = 'almokhlif-oud-sales-report';
		$this->plugin_file = ALMOKHLIF_OUDSR_PLUGIN_FILE;
		$this->current_version = ALMOKHLIF_OUDSR_VERSION;
		
		// Get GitHub settings from WordPress options
		$github_settings = get_option( 'almokhlif_oud_sales_report_github_settings', array() );
		$this->github_owner = isset( $github_settings['owner'] ) ? $github_settings['owner'] : '';
		$this->github_repo = isset( $github_settings['repo'] ) ? $github_settings['repo'] : '';
		$this->github_token = isset( $github_settings['token'] ) ? $github_settings['token'] : '';
		
		// Only initialize if GitHub settings are configured
		if ( ! empty( $this->github_owner ) && ! empty( $this->github_repo ) ) {
			$this->init();
		}
	}
	
	/**
	 * Initialize update checker
	 */
	private function init() {
		// Check for updates every 12 hours
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_updates' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'post_install' ), 10, 3 );
	}
	
	/**
	 * Check for updates
	 *
	 * @param object $transient Update transient
	 * @return object
	 */
	public function check_for_updates( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}
		
		$latest_release = $this->get_latest_release();
		
		if ( $latest_release && version_compare( $this->current_version, $latest_release['version'], '<' ) ) {
			$plugin_data = get_plugin_data( $this->plugin_file );
			
			$transient->response[ $this->plugin_slug . '/' . basename( $this->plugin_file ) ] = (object) array(
				'slug'        => $this->plugin_slug,
				'plugin'      => $this->plugin_slug . '/' . basename( $this->plugin_file ),
				'new_version' => $latest_release['version'],
				'url'         => $latest_release['url'],
				'package'     => $latest_release['download_url'],
				'icons'       => array(),
				'banners'     => array(),
				'tested'      => '',
				'requires_php' => '7.4',
			);
		}
		
		return $transient;
	}
	
	/**
	 * Get plugin information for update screen
	 *
	 * @param false|object|array $result Result
	 * @param string             $action Action
	 * @param object             $args   Arguments
	 * @return false|object
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( $action !== 'plugin_information' || $args->slug !== $this->plugin_slug ) {
			return $result;
		}
		
		$latest_release = $this->get_latest_release();
		
		if ( ! $latest_release ) {
			return $result;
		}
		
		$plugin_data = get_plugin_data( $this->plugin_file );
		
		$result = (object) array(
			'slug'          => $this->plugin_slug,
			'name'          => $plugin_data['Name'],
			'version'       => $latest_release['version'],
			'author'        => $plugin_data['AuthorName'],
			'author_profile' => $plugin_data['AuthorURI'],
			'homepage'      => $plugin_data['PluginURI'],
			'short_description' => $plugin_data['Description'],
			'sections'      => array(
				'description' => $plugin_data['Description'],
				'changelog'   => $latest_release['changelog'],
			),
			'download_link' => $latest_release['download_url'],
			'banners'       => array(),
			'icons'         => array(),
		);
		
		return $result;
	}
	
	/**
	 * Post install hook
	 *
	 * @param bool  $response   Response
	 * @param array $hook_extra Extra hook data
	 * @param array $result     Result
	 * @return bool
	 */
	public function post_install( $response, $hook_extra, $result ) {
		if ( isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] === $this->plugin_slug . '/' . basename( $this->plugin_file ) ) {
			// Move plugin to correct location
			$install_directory = plugin_dir_path( $this->plugin_file );
			$global_directory = $result['destination'] . '/' . dirname( $hook_extra['plugin'] );
			
			if ( $install_directory !== $global_directory ) {
				$file_manager = new WP_Filesystem_Direct( null );
				$file_manager->move( $global_directory, $install_directory );
			}
			
			activate_plugin( $this->plugin_slug . '/' . basename( $this->plugin_file ) );
		}
		
		return $response;
	}
	
	/**
	 * Get latest release from GitHub
	 *
	 * @return array|false Release data or false on failure
	 */
	private function get_latest_release() {
		$cache_key = 'almokhlif_oud_sr_latest_release';
		$cached = get_transient( $cache_key );
		
		if ( $cached !== false ) {
			return $cached;
		}
		
		$api_url = sprintf(
			'https://api.github.com/repos/%s/%s/releases/latest',
			$this->github_owner,
			$this->github_repo
		);
		
		$args = array(
			'timeout' => 15,
			'headers' => array(
				'Accept' => 'application/vnd.github.v3+json',
				'User-Agent' => 'WordPress-Plugin-Update-Checker',
			),
		);
		
		// Add token for private repositories
		if ( ! empty( $this->github_token ) ) {
			$args['headers']['Authorization'] = 'token ' . $this->github_token;
		}
		
		$response = wp_remote_get( $api_url, $args );
		
		if ( is_wp_error( $response ) ) {
			return false;
		}
		
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		if ( ! isset( $data['tag_name'] ) ) {
			return false;
		}
		
		// Extract version from tag (remove 'v' prefix if present)
		$version = ltrim( $data['tag_name'], 'v' );
		
		// Find zipball download URL
		$download_url = '';
		if ( isset( $data['zipball_url'] ) ) {
			$download_url = $data['zipball_url'];
			// Add token to download URL for private repos
			if ( ! empty( $this->github_token ) ) {
				$download_url = add_query_arg( 'access_token', $this->github_token, $download_url );
			}
		} elseif ( isset( $data['assets'] ) && is_array( $data['assets'] ) ) {
			// Look for a zip asset
			foreach ( $data['assets'] as $asset ) {
				if ( isset( $asset['browser_download_url'] ) && strpos( $asset['browser_download_url'], '.zip' ) !== false ) {
					$download_url = $asset['browser_download_url'];
					break;
				}
			}
		}
		
		$release_data = array(
			'version'      => $version,
			'url'          => isset( $data['html_url'] ) ? $data['html_url'] : '',
			'download_url' => $download_url,
			'changelog'    => isset( $data['body'] ) ? wp_kses_post( $data['body'] ) : '',
		);
		
		// Cache for 12 hours
		set_transient( $cache_key, $release_data, 12 * HOUR_IN_SECONDS );
		
		return $release_data;
	}
	
	/**
	 * Get instance
	 *
	 * @return Almokhlif_Oud_Sales_Report_Update_Checker
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new self();
		}
		return $instance;
	}
}

