<?php

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local as LocalAdapter;

class Brizy_Admin_OptimizeImages {

	const PAGE_KEY = 'brizy-optimize-images';

	/**
	 * @var Brizy_TwigEngine
	 */
	private $twig;

	/**
	 * @return Brizy_Admin_OptimizeImages
	 */
	public static function _init() {
		static $instance;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * @return string
	 */
	public static function menu_slug() {
		return self::PAGE_KEY;
	}

	/**
	 * Brizy_Admin_OptimizeImages constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'addSubmenuPage' ), 11 );

		$this->twig = Brizy_TwigEngine::instance( BRIZY_PLUGIN_PATH . "/admin/views/settings/" );
	}

	public function addSubmenuPage() {
		add_submenu_page( Brizy_Admin_Settings::menu_slug(),
			__( 'Optimize Images' ),
			__( 'Optimize Images' ),
			'manage_options',
			self::menu_slug(),
			array( $this, 'render' )
		);
	}

	public function render() {

		$urlBuilder        = new Brizy_Editor_UrlBuilder( Brizy_Editor_Project::get() );
		$brizy_upload_path = $urlBuilder->brizy_upload_path();
		$adapter           = new LocalAdapter( $brizy_upload_path );
		$filesystem        = new Filesystem( $adapter );

		$brizy_ids = Brizy_Editor_Post::get_all_brizy_post_ids();
		$urls      = array();
		foreach ( $brizy_ids as $id ) {
			$brizyPost = Brizy_Editor_Post::get( $id );

			if ( ! $brizyPost->get_compiled_html() ) {
				$content = $brizyPost->get_compiled_html_body();
			} else {
				$compiled_page = $brizyPost->get_compiled_page();
				$content       = $compiled_page->get_head().$compiled_page->get_body();
			}

			$content = Brizy_SiteUrlReplacer::restoreSiteUrl( $content );

			$content = apply_filters( 'brizy_content', $content, Brizy_Editor_Project::get(), $brizyPost->get_wp_post() );

			$urls = array_merge( $urls, $this->extract_media_urls( $content, $filesystem ) );
		}

		$urls = array_unique($urls);

		$context = array(
			'urls'  => $urls,
			'count' => count( $urls ),
			'submit_label' => __('Optimize images')

		);

		echo $this->twig->render( 'image-optimization.html.twig', $context );
	}


	/**
	 * @param string $content
	 * @param Brizy_Content_Context $context
	 *
	 * @return mixed
	 */
	public function extract_media_urls( $content, $filesystem ) {

		$result   = array();
		$site_url = str_replace( array( 'http://', 'https://' ), '', home_url() );

		$site_url = str_replace( array( '/', '.' ), array( '\/', '\.' ), $site_url );

		preg_match_all( '/' . $site_url . '\/?(\?' . Brizy_Public_CropProxy::ENDPOINT . '=(.[^"\',\s)]*))/im', $content, $matches );
		preg_match_all( '/(http|https):\/\/' . $site_url . '\/?(\?' . Brizy_Public_CropProxy::ENDPOINT . '=(.[^"\',\s)]*))/im', $content, $matches );

		if ( ! isset( $matches[0] ) || count( $matches[0] ) == 0 ) {
			return $result;
		}

		$time = time();

		foreach ( $matches[0] as $i => $url ) {

			$parsed_url = parse_url( html_entity_decode( $matches[0][ $i ] ) );

			if ( ! isset( $parsed_url['query'] ) ) {
				continue;
			}

			parse_str( $parsed_url['query'], $params );


			if ( ! isset( $params[ Brizy_Public_CropProxy::ENDPOINT ] ) ) {
				continue;
			}

			$imageFullName = sprintf( "%s/assets/images/%s/optimized/%s", $params['brizy_post'], $params['brizy_crop'], $params['brizy_media'] );

			try {
				$filesystem->get( $imageFullName );
			} catch ( Exception $e ) {
				$result[] = $url . "&brizy_optimize=1&t=" . $time;
			}
		}

		return $result;
	}

}