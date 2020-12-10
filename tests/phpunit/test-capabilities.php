<?php
/**
 * User capability tests for the plugin.
 *
 * @package authorship
 *
 * @TODO this entire test class needs to also run against:
 *
 *  - a CPT with `map_meta_cap` set to `true`
 *  - a CPT with `map_meta_cap` set to `false`
 *  - posts with post status of type that is not `public`
 *  - posts with post status of type that is `private`
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\POSTS_PARAM;

class TestCapabilities extends TestCase {
	/**
	 * @dataProvider dataRolesAndCaps
	 *
	 * @param string $role Role name
	 * @param mixed[] $caps Caps
	 */
	public function testUserCanManagePostTheyAreAttributedTo( string $role, array $caps ) : void {
		$factory = self::factory()->post;
		$user_id = self::$users[ $role ]->ID;

		// Attributed to user, owned by Admin.
		$draft_post = $factory->create_and_get( [
			'post_status' => 'draft',
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$user_id,
			],
		] );

		// Attributed to user, owned by Admin.
		$published_post = $factory->create_and_get( [
			'post_status' => 'publish',
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$user_id,
			],
		] );

		// Attributed to user, owned by Admin.
		$scheduled_post = $factory->create_and_get( [
			'post_status' => 'future',
			'post_date'   => date( 'Y-m-d H:i:s', strtotime( '+24 hours' ) ),
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$user_id,
			],
		] );

		// Draft post:
		$this->assertSame( $caps['draft']['edit_post'], user_can( $user_id, 'edit_post', $draft_post->ID ) );
		$this->assertSame( $caps['draft']['publish_post'], user_can( $user_id, 'publish_post', $draft_post->ID ) );
		$this->assertSame( $caps['draft']['read_post'], user_can( $user_id, 'read_post', $draft_post->ID ) );
		$this->assertSame( $caps['draft']['delete_post'], user_can( $user_id, 'delete_post', $draft_post->ID ) );

		// Published post:
		$this->assertSame( $caps['publish']['edit_post'], user_can( $user_id, 'edit_post', $published_post->ID ) );
		$this->assertSame( $caps['publish']['publish_post'], user_can( $user_id, 'publish_post', $published_post->ID ) );
		$this->assertSame( $caps['publish']['read_post'], user_can( $user_id, 'read_post', $published_post->ID ) );
		$this->assertSame( $caps['publish']['delete_post'], user_can( $user_id, 'delete_post', $published_post->ID ) );

		// Scheduled post:
		$this->assertSame( $caps['future']['edit_post'], user_can( $user_id, 'edit_post', $scheduled_post->ID ) );
		$this->assertSame( $caps['future']['publish_post'], user_can( $user_id, 'publish_post', $scheduled_post->ID ) );
		$this->assertSame( $caps['future']['read_post'], user_can( $user_id, 'read_post', $scheduled_post->ID ) );
		$this->assertSame( $caps['future']['delete_post'], user_can( $user_id, 'delete_post', $scheduled_post->ID ) );
	}

	/**
	 * @return mixed[]
	 */
	public function dataRolesAndCaps() : array {
		return [
			[
				'editor',
				[
					'draft' => [
						'edit_post'    => true,
						'publish_post' => true,
						'read_post'    => true,
						'delete_post'  => true,
					],
					'publish' => [
						'edit_post'    => true,
						'publish_post' => true,
						'read_post'    => true,
						'delete_post'  => true,
					],
					'future' => [
						'edit_post'    => true,
						'publish_post' => true,
						'read_post'    => true,
						'delete_post'  => true,
					],
				],
			],
			[
				'author',
				[
					'draft' => [
						'edit_post'    => true,
						'publish_post' => true,
						'read_post'    => true,
						'delete_post'  => true,
					],
					'publish' => [
						'edit_post'    => true,
						'publish_post' => true,
						'read_post'    => true,
						'delete_post'  => true,
					],
					'future' => [
						'edit_post'    => true,
						'publish_post' => true,
						'read_post'    => true,
						'delete_post'  => true,
					],
				],
			],
			[
				'contributor',
				[
					'draft' => [
						'edit_post'    => true,
						'publish_post' => false,
						'read_post'    => true,
						'delete_post'  => true,
					],
					'publish' => [
						'edit_post'    => false,
						'publish_post' => false,
						'read_post'    => true,
						'delete_post'  => false,
					],
					'future' => [
						'edit_post'    => false,
						'publish_post' => false,
						'read_post'    => false, // @TODO Can a Contributor not read their own scheduled post?
						'delete_post'  => false,
					],
				],
			],
			[
				'subscriber',
				[
					'draft' => [
						'edit_post'    => false,
						'publish_post' => false,
						'read_post'    => false,
						'delete_post'  => false,
					],
					'publish' => [
						'edit_post'    => false,
						'publish_post' => false,
						'read_post'    => true,
						'delete_post'  => false,
					],
					'future' => [
						'edit_post'    => false,
						'publish_post' => false,
						'read_post'    => false,
						'delete_post'  => false,
					],
				],
			],
		];
	}
}
