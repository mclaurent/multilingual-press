<?php # -*- coding: utf-8 -*-

declare( strict_types = 1 );

namespace Inpsyde\MultilingualPress\Translation\Post;

use Inpsyde\MultilingualPress\Translation\Metabox\MetaboxInfo;
use Inpsyde\MultilingualPress\Translation\Metabox\MetaboxView;
use Inpsyde\MultilingualPress\Translation\Metabox\MetaboxUpdater;
use Inpsyde\MultilingualPress\Translation\Metabox\SiteSpecificMetabox;

/**
 * @package Inpsyde\MultilingualPress\Translation\Metabox
 * @since   3.0.0
 */
final class PostTranslationMetabox implements SiteSpecificMetabox {

	/**
	 * @var \WP_Post
	 */
	private $post;
	/**
	 * @var int
	 */
	private $site_id;
	/**
	 * @var string
	 */
	private $language;

	/**
	 * @var array
	 */
	private $post_types;

	/**
	 * Constructor.
	 *
	 * @param int      $site_id
	 * @param string   $language
	 * @param array    $post_types
	 * @param \WP_Post $post
	 */
	public function __construct( int $site_id, string $language, array $post_types, \WP_Post $post = null ) {

		$this->site_id = $site_id;

		$this->language = $language;

		$this->post_types = $post_types;

		$this->post = $post;
	}

	/**
	 * @return int
	 */
	public function site_id(): int {

		return $this->site_id;
	}

	/**
	 * @return MetaboxInfo
	 */
	public function info(): MetaboxInfo {

		return new PostTranslationMetaboxInfo( $this->site_id, $this->language, $this->post_types, $this->post );

	}

	/**
	 * @return MetaboxView
	 */
	public function view(): MetaboxView {

		$view = new PostTranslationMetaboxView( $this->site_id, $this->language, $this->post );

		/**
		 * Runs just after PostTranslationMetaboxView is initialized.
		 *
		 * Useful to inject data via PostTranslationMetaboxView::with_data().
		 *
		 * @param PostTranslationMetaboxView $view View object.
		 */
		do_action( 'multilingualpress.post_translation_view', $view );

		return $view;
	}

	/**
	 * @return MetaboxUpdater
	 */
	public function updater(): MetaboxUpdater {

		$updater = new PostTranslationMetaboxUpdater( $this->site_id, $this->language, $this->post );

		/**
		 * Runs just after PostTranslationMetaboxUpdater is initialized.
		 *
		 * Useful to inject data via PostTranslationMetaboxUpdater::with_data().
		 *
		 * @param PostTranslationMetaboxUpdater $updater Updater object.
		 */
		do_action( 'multilingualpress.post_translation_updater', $updater );

		return $updater;
	}
}