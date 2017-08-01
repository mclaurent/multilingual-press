<?php # -*- coding: utf-8 -*-

declare( strict_types = 1 );

namespace Inpsyde\MultilingualPress\Widget\Dashboard\UntranslatedPosts;

use Inpsyde\MultilingualPress\Translation\Post\ActivePostTypes;

/**
 * Type-safe untranslated posts repository implementation.
 *
 * @package Inpsyde\MultilingualPress\Widget\Dashboard\UntranslatedPosts
 * @since   3.0.0
 */
final class TypeSafePostsRepository implements PostsRepository {

	/**
	 * @var ActivePostTypes
	 */
	private $active_post_types;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @since 3.0.0
	 *
	 * @param ActivePostTypes $active_post_types Active post types storage object.
	 */
	public function __construct( ActivePostTypes $active_post_types ) {

		$this->active_post_types = $active_post_types;
	}

	/**
	 * Returns all untranslated posts for the current site.
	 *
	 * @since 3.0.0
	 *
	 * @return \WP_Post[] All untranslated posts for the current site.
	 */
	public function get_untranslated_posts(): array {

		return (array) get_posts( [
			'post_type'        => $this->active_post_types->names(),
			// Not suppressing filters (which is done by default when using get_posts()) makes caching possible.
			'suppress_filters' => false,
			// Post status 'any' automatically excludes both 'auto-draft' and 'trash'.
			'post_status'      => 'any',
			'meta_query'       => [
				[
					'key'     => PostsRepository::META_KEY,
					'compare' => '!=',
					'value'   => true,
				],
			],
		] );
	}

	/**
	 * Checks if the post with the given ID has been translated.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id Optional. Post ID. Defaults to 0.
	 *
	 * @return bool Whether or not the post with the given ID has been translated.
	 */
	public function is_post_translated( int $post_id = 0 ): bool {

		$post_id = $post_id ?: (int) get_the_ID();

		return (bool) get_post_meta( $post_id, PostsRepository::META_KEY, true );
	}

	/**
	 * Updates the translation complete setting value for the post with the given ID.
	 *
	 * @since 3.0.0
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $value   Setting value to be set.
	 *
	 * @return bool Whether or not the translation complete setting value was updated successfully.
	 */
	public function update_post( int $post_id, bool $value ): bool {

		return (bool) update_post_meta( $post_id, PostsRepository::META_KEY, (bool) $value );
	}
}
