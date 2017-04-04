<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Common\HTTP;

/**
 * Interface for all HTTP request abstraction implementations.
 *
 * @package Inpsyde\MultilingualPress\Common\HTTP
 * @since   3.0.0
 */
interface Request {

	/**
	 * Returns the URL for current request.
	 *
	 * @return URL
	 */
	public function url(): URL;

	/**
	 * Return a value from request body, optionally filtered.
	 *
	 * @param string $name
	 * @param int    $method
	 * @param int    $filter
	 * @param null   $options
	 *
	 * @return mixed
	 */
	public function body_value( string $name, int $method = INPUT_REQUEST, int $filter = FILTER_UNSAFE_RAW, $options = null );

	/**
	 * Returns header value as set in the request.
	 *
	 * @param string $name Header name
	 *
	 * @return string
	 */
	public function header( string $name ): string;

	/**
	 * Returns a parsed header value.
	 *
	 * @param string            $name   Header name.
	 * @param HeaderParser|null $parser Header parser to be used. Implementations can decide how to convert raw header
	 *                                  string to array if not provided.
	 *
	 * @return array
	 */
	public function parsed_header( string $name, HeaderParser $parser = null ): array;
}
