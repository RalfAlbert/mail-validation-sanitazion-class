<?php
class Mail_Local_Filter
{
	public $whynot = array();

	/**
	 * Validate the local part of an email address
	 * @param	string	$local	Local part of an email address
	 * @return	boolean			True if the local part is valid
	 */
	public function validate_local_part( $local ) {
		/*
		 * The maximum total length of a user name or other local-part is 64 octets
		 *
		 * @see http://tools.ietf.org/html/rfc5321#section-4.5.3.1.1
		 */
		if ( 64 < strlen( $local ) ) {
			array_push( $this->whynot, 'Local part is too long, it contains more than 64 characters' );
			return false;
		}

		/*
		 * Too much dots tests
		 *
		 * "period (".") may also appear, but may not be used to start or end the local part,
		 * nor may two or more consecutive periods appear."
		 *
		 * @see http://tools.ietf.org/html/rfc3696#section-3
		 */
		$too_much_dots = preg_match( '#\.{2,}#', $local );
		if ( $too_much_dots ) {
			array_push( $this->whynot, 'Mail contains one or more period of dots' );
		}

		/*
		 * The local part should not start or end with a dot
		 *
		 * "period (".") may also appear, but may not be used to start or end the local part"
		 *
		 * @see: http://tools.ietf.org/html/rfc3696#section-3
		 */
		$dot_start_end = preg_match( '#(^\.|\.$)#', $local );
		if ( $dot_start_end ) {
			array_push( $this->whynot, 'Local part should not start or end with a dot. ' );
			return false;
		}

		/*
		 * Handle commented mail adresses
		 */
		$local_back = $local;
		if ( preg_match( '#\([^\)]+\)+#', $local ) ) {
			$local = preg_replace( sprintf( '#(^%1$s|%1$s$)#', '\([^\)]+\)' ), '', $local );
			if ( $local == $local_back ) {
				array_push( $this->whynot, 'Local part contains comment at wrong place.' );
				return false;
			}
		}

		/*
		 * Handle invalid characters
		 * "Without quotes, local-parts may consist of any combination of alphabetic characters, digits,
		 * or any of the special characters ! # $ % & ' * + - / = ?  ^ _ ` . { | } ~"
		 *
		 * @see: http://tools.ietf.org/html/rfc3696#section-3
		 */
		$special_chars = preg_replace( '#[a-zA-z0-9\.\!\#\$\%\&\'\*\+\-\/\=\?\^\_`\{\|\}\~]+#', '', $local );
		$is_enclosed   = preg_match( '#^\"([^"]+)\"#', $local, $enclosed_parts );
		if ( empty( $enclosed_parts ) && ! empty( $special_chars ) ) {

			$is_valid = self::handle_special_chars_in_local_part( $local, $is_enclosed );

			if ( ! $is_valid ) {
				array_push( $this->whynot, 'Local part contains not allowed characters.');
				return false;
			}
		}


		return true;
	}

	/**
	 * Validate if the local part contains escaped or quoted special chars.
	 * Returns true if it does not contain any special chars
	 * Returns true if the whole local part is enclosed in double qoutes (quoted).
	 * Returns true if the local part contains ecaped special chars in a non quoted local part
	 * Returns false if it contain special chars that are neither in a quoted local part or are escaped
	 *
	 * @param	string		$local			The local part to be validated
	 * @param	int|boolean	$is_enclosed	Flag if the local part is enclosed in double quotes
	 * @return	boolean						True if special chars are escaped or the local part is enclosed in double quotes
	 */
	private function handle_special_chars_in_local_part( $local, $is_enclosed ) {
		/*
		 * Escaped or quoted special characters
		 *
		 * "The exact rule is that any ASCII character, including control characters, may appear quoted,
		 * or in a quoted string. When quoting is needed, the backslash character is used to quote the
		 * following character.
		 * In addition to quoting using the backslash character, conventional double-quote characters
		 * may be used to surround strings."
		 *
		 * These chars can be appear within a quoted local part or in a not quoted local part, as quoted
		 * single chars.
		 * "(),:;<>@[\] and [space]
		 *
		 * These chars have to be quoted, either they appear in a quoted local part or not
		 * @ \ " , [ ]
		 *
		 * @see: http://tools.ietf.org/html/rfc3696#section-3
		 */
		$has_special_chars = preg_match( '#[\s\"\(\)\,\:\;\<\>\@\[\]\\\]#', $local );

		if ( $has_special_chars && ! $is_enclosed ) {
			// looking for escaped special chars, strip all escaped special chars
			$clean_local             = preg_replace( '#(\\\\\s|\\\"|\\\\\)|\\\\\(|\\\,|\\\:|\\\;|\\\<|\\\>|\\\@|\\\\\]|\\\\\[|\\\\\\\)#', '', $local );
			$has_still_special_chars = preg_match_all( '#[\s' . preg_quote( '"(),:;<>@[]' ) . '\\\]#', $clean_local, $special_chars );

			if ( $has_still_special_chars ) {
				$chars = implode( ' ', $special_chars[0] );
				array_push( $this->whynot, 'Found these unescapede special characters in local part that is not enclosed in double quotes: ' . $chars );
				return false;
			}

			// has no special chars in a non enqouted local part
			return true;
		}

		// has no special chars, either in an enqouted or non enqouted local part
		return true;
	}

}