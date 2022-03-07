<?php
/**
 * REST API user endpoint tests.
 *
 * This endpoint is as extension of the `wp/v2/users` endpoint, therefore its
 * tests take this into account by asserting that various fields are not exposed
 * and various filters are not available, and also by not testing functionality
 * that is natively provided by the WordPress endpoint such as search and sort.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use Authorship\Users_Controller;

use const Authorship\GUEST_ROLE;

use WP_Http;
use WP_REST_Request;

class TestRESTAPIUserEndpoint extends RESTAPITestCase {
	/**
	 * @var string
	 */
	protected static $route = '/' . Users_Controller::_NAMESPACE . '/' . Users_Controller::BASE;

	public function testGuestAuthorCanBeCreatedWithJustAName() : void {
		wp_set_current_user( self::$users['editor']->ID );

		$request = new WP_REST_Request( 'POST', self::$route );
		$request->set_param( 'name', 'Firsty Lasty' );

		$response = self::rest_do_request( $request );
		$data = $response->get_data();
		$message = self::get_message( $response );

		$this->assertSame( WP_Http::CREATED, $response->get_status(), $message );
		$this->assertSame( [ GUEST_ROLE ], $data['roles'] );
	}

	/**
	 * @dataProvider dataDisallowedFields
	 *
	 * @param string $param
	 */
	public function testFieldCannotBeSpecifiedWhenCreatingGuestAuthor( string $param ) : void {
		wp_set_current_user( self::$users['editor']->ID );

		$request = new WP_REST_Request( 'POST', self::$route );
		$request->set_param( 'name', 'Firsty Lasty' );
		$request->set_param( $param, 'testing' );

		$response = self::rest_do_request( $request );
		$message = self::get_message( $response );

		$this->assertSame( WP_Http::FORBIDDEN, $response->get_status(), $message );
	}

	public function testUserOutputFieldsAreRestrictedWhenListingUsers() : void {
		wp_set_current_user( self::$users['editor']->ID );

		$request = new WP_REST_Request( 'GET', self::$route );
		$request->set_param( 'search', 'editor' );
		$request->set_param( 'post_type', 'post' );

		$response = self::rest_do_request( $request );
		$data = $response->get_data();
		$message = self::get_message( $response );
		$expected = [
			'id',
			'name',
			'link',
			'slug',
			'avatar_urls',
			'_links',
		];

		$this->assertSame( WP_Http::OK, $response->get_status(), $message );
		$this->assertEqualSets( $expected, array_keys( $data[0] ) );
	}

	public function testAllUsersAreListedWhenListingUsers() : void {
		wp_set_current_user( self::$users['editor']->ID );

		$request = new WP_REST_Request( 'GET', self::$route );
		$request->set_param( 'post_type', 'post' );

		$response = self::rest_do_request( $request );
		$data = $response->get_data();
		$message = self::get_message( $response );

		$this->assertSame( WP_Http::OK, $response->get_status(), $message );
		$this->assertCount( count( self::$users ) + 1, $data );
	}

	/**
	 * @dataProvider dataDisallowedFilters
	 *
	 * @param string $param
	 */
	public function testUsersCannotBeFilteredByParameter( string $param ) : void {
		wp_set_current_user( self::$users['editor']->ID );

		$request = new WP_REST_Request( 'GET', self::$route );
		$request->set_param( $param, 'testing' );

		$response = self::rest_do_request( $request );
		$message = self::get_message( $response );

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status(), $message );
	}

	public function testContextCannotBeSetToEditWhenListingUsers() : void {
		wp_set_current_user( self::$users['editor']->ID );

		$request = new WP_REST_Request( 'GET', self::$route );
		$request->set_param( 'context', 'edit' );

		$response = self::rest_do_request( $request );
		$message = self::get_message( $response );

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status(), $message );
	}

	public function testPostTypeIsRequiredWhenListingUsers() : void {
		wp_set_current_user( self::$users['editor']->ID );

		$request = new WP_REST_Request( 'GET', self::$route );

		$response = self::rest_do_request( $request );
		$message = self::get_message( $response );

		/** @var \WP_Error */
		$error = $response->as_error();
		$data = $error->get_error_data();

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status(), $message );
		$this->assertArrayHasKey( 'params', $data );
		$this->assertSame( [ 'post_type' ], $data['params'] );
	}

	public function testEndpointRequiresAuthentication() : void {
		$request = new WP_REST_Request( 'GET', self::$route );
		$request->set_param( 'search', 'testing' );
		$request->set_param( 'post_type', 'post' );

		$response = self::rest_do_request( $request );
		$message = self::get_message( $response );

		$this->assertSame( WP_Http::UNAUTHORIZED, $response->get_status(), $message );
	}

	/**
	 * @dataProvider dataAllowedOrderby
	 *
	 * @param string $orderby
	 */
	public function testAllowedOrderByParameters( string $orderby ) : void {
		wp_set_current_user( self::$users['editor']->ID );

		$request = new WP_REST_Request( 'GET', self::$route );
		$request->set_param( 'orderby', $orderby );
		$request->set_param( 'post_type', 'post' );

		$response = self::rest_do_request( $request );
		$message = self::get_message( $response );

		$this->assertSame( WP_Http::OK, $response->get_status(), $message );
	}

	/**
	 * @dataProvider dataDisallowedOrderby
	 *
	 * @param string $orderby
	 */
	public function testDisallowedOrderByParameters( string $orderby ) : void {
		wp_set_current_user( self::$users['editor']->ID );

		$request = new WP_REST_Request( 'GET', self::$route );
		$request->set_param( 'orderby', $orderby );
		$request->set_param( 'post_type', 'post' );

		$response = self::rest_do_request( $request );
		$message = self::get_message( $response );

		$this->assertTrue( $response->is_error() );

		/** @var \WP_Error */
		$error = $response->as_error();
		$data = $error->get_error_data();

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status(), $message );
		$this->assertArrayHasKey( 'params', $data );
		$this->assertArrayHasKey( 'orderby', $data['params'] );
	}

	/**
	 * @dataProvider dataRolesThatCanAttributeAuthors
	 *
	 * @param string $role
	 * @param bool   $expected
	 */
	public function testUserRolesThatCanListAuthors( string $role, bool $expected ) : void {
		wp_set_current_user( self::$users[ $role ]->ID );

		$request = new WP_REST_Request( 'GET', self::$route );
		$request->set_param( 'post_type', 'post' );

		$response = self::rest_do_request( $request );
		$message = self::get_message( $response );

		if ( $expected ) {
			$this->assertSame( WP_Http::OK, $response->get_status(), $message );
		} else {
			$this->assertSame( WP_Http::FORBIDDEN, $response->get_status(), $message );
		}
	}

	/**
	 * @dataProvider dataRolesThatCanCreateGuestAuthors
	 *
	 * @param string $role
	 * @param bool   $expected
	 */
	public function testUserRolesThatCanCreateGuestAuthors( string $role, bool $expected ) : void {
		wp_set_current_user( self::$users[ $role ]->ID );

		$request = new WP_REST_Request( 'POST', self::$route );
		$request->set_param( 'name', 'testing' );

		$response = self::rest_do_request( $request );
		$message = self::get_message( $response );

		if ( $expected ) {
			$this->assertSame( WP_Http::CREATED, $response->get_status(), $message );
		} else {
			$this->assertSame( WP_Http::FORBIDDEN, $response->get_status(), $message );
		}
	}

	/**
	 * @dataProvider dataRolesThatCanManageUsers
	 *
	 * @param string $role
	 * @param bool   $expected
	 */
	public function testGuestAuthorEmailIsAllowed( string $role, bool $expected ) : void {
		wp_set_current_user( self::$users[ $role ]->ID );

		$request = new WP_REST_Request( 'POST', self::$route );
		$request->set_param( 'name', 'testing' );
		$request->set_param( 'email', 'test@example.org' );

		$response = self::rest_do_request( $request );
		$message = self::get_message( $response );

		if ( $expected ) {
			$this->assertSame( WP_Http::CREATED, $response->get_status(), $message );
		} else {
			$this->assertSame( WP_Http::FORBIDDEN, $response->get_status(), $message );
		}
	}

	/**
	 * @return mixed[]
	 */
	public function dataAllowedOrderby() : array {
		return [
			[
				'id',
			],
			[
				'name',
			],
		];
	}

	/**
	 * @return mixed[]
	 */
	public function dataDisallowedOrderby() : array {
		return [
			[
				'registered_date',
			],
			[
				'slug',
			],
			[
				'include_slugs',
			],
			[
				'email',
			],
			[
				'url',
			],
		];
	}

	/**
	 * @return mixed[]
	 */
	public function dataDisallowedFilters() : array {
		return [
			[
				'roles',
			],
			[
				'slug',
			],
			[
				'capabilities',
			],
			[
				'who',
			],
		];
	}

	/**
	 * @return mixed[]
	 */
	public function dataDisallowedFields() : array {
		return [
			[
				'password',
			],
			[
				'roles',
			],
		];
	}

	/**
	 * @return mixed[]
	 */
	public function dataRolesThatCanCreateGuestAuthors() : array {
		return [
			[
				'admin',
				true,
			],
			[
				'editor',
				true,
			],
			[
				'author',
				false,
			],
			[
				'contributor',
				false,
			],
			[
				'subscriber',
				false,
			],
		];
	}

	/**
	 * @return mixed[]
	 */
	public function dataRolesThatCanAttributeAuthors() : array {
		return [
			[
				'admin',
				true,
			],
			[
				'editor',
				true,
			],
			[
				'author',
				false,
			],
			[
				'contributor',
				false,
			],
			[
				'subscriber',
				false,
			],
		];
	}

	/**
	 * @return mixed[]
	 */
	public function dataRolesThatCanManageUsers() : array {
		return [
			[
				'admin',
				( ! is_multisite() ),
			],
			[
				'editor',
				false,
			],
			[
				'author',
				false,
			],
			[
				'contributor',
				false,
			],
			[
				'subscriber',
				false,
			],
		];
	}
}
