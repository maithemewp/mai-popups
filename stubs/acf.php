<?php

/**
 * Minimal stubs for Advanced Custom Fields (Pro) functions used by this plugin.
 *
 * ACF is an external, optional dependency that is not installed via Composer,
 * so PHPStan has no knowledge of these functions. These declarations exist
 * solely to satisfy static analysis and are never loaded at runtime.
 *
 * @see https://www.advancedcustomfields.com/resources/
 */

/**
 * @param string $selector
 * @param int|string|false $post_id
 * @param bool $format_value
 * @return mixed
 */
function get_field( $selector = '', $post_id = false, $format_value = true ) {}

/**
 * @param int|string|false $post_id
 * @param bool $format_value
 * @return array<string,mixed>|false
 */
function get_fields( $post_id = false, $format_value = true ) {}

/**
 * @param array<string,mixed> $field_group
 * @return array<string,mixed>
 */
function acf_add_local_field_group( $field_group ) {}
