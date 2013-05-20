<?php
/**
 * This class ignores some rules defined in the RFC 822 (ARPA INTERNET TEXT MESSAGES)
 *
 * @author Ralf Albert
 *
 * @see http://tools.ietf.org/html/rfc822
 * @see http://tools.ietf.org/html/rfc3696
 * @see http://tools.ietf.org/html/rfc5322
 * @see http://tools.ietf.org/html/rfc5321
 *
 */

class Mail
{
	public $whynot = array();
	public $mail   = '';

	/**
	 * Constructor
	 * @param	string	$mail	Email address to be validated or sanitized
	 */
	public function __construct( $mail = '' ) {
		$this->mail = $mail;
	}

	/**
	 * Validate an email
	 * @param	string	$mail 	Email address to be validated
	 * @return	boolean			True if the mail-adress is valid
	 */
	public function is_mail( $mail = '' ) {
		if ( empty( $mail ) && empty( $this->mail ) ) {
			array_push( $this->whynot, 'No mail given' );
			return false;
		} elseif( ! empty( $this->mail ) ) {
			$mail = $this->mail;
		}

		/*
		 * "In addition to restrictions on syntax, there is a length limit on email addresses.
		 * That limit is a maximum of 64 characters (octets) in the "local part" (before the "@")
		 * and a maximum of 255 characters (octets) in the domain part (after the "@") for a total
		 * length of 320 characters."
		 *
		 * @see: http://tools.ietf.org/html/rfc3696#section-3
		 *
		 * "The maximum total length of a user name or other local-part is 64 octets."
		 * @see http://tools.ietf.org/html/rfc5321#section-4.5.3.1
		 *
		 * "The maximum total length of a domain name or number is 255 octets."
		 * @see http://tools.ietf.org/html/rfc5321#section-4.5.3.2
		 *
		 * "The maximum total length of a reverse-path or forward-path is 256 octets (including the
		 * punctuation and element separators)."
		 * @see http://tools.ietf.org/html/rfc5321#section-4.5.3.3
		 *
		 * If an email address is used for forwarding or response, the maximum length is 256 by a theoretical
		 * length of 320 characters
		 */
		if ( 256 < strlen( $mail ) ) {
			array_push( $this->whynot, 'Mail is too long, it contains more than 256 characters.' );
			return false;
		}

		/*
		 * Split mail into local part and domain. Do not use explode(), because the local part can contain the @ char
		 * Contemporary email addresses consist of a "local part" separated from a "domain part"
		 * (a fully-qualified domain name) by an at-sign ("@").
		 *
		 * @see: http://tools.ietf.org/html/rfc3696#section-3
		 */
		preg_match( '#(.+)@([^@]+)$#', $mail, $parts );

		// the mail apparently contain no @
		if ( 3 > sizeof( $parts ) ) {
			array_push( $this->whynot, 'mail contain apparently no @ sign' );
			return false;
		}

		list( $mail, $local, $domain ) = $parts;

		/*
		 * Test the local part and the domain
		 */
		$result_local  = $this->validate_local_part( $local );
		$result_domain = $this->validate_domain_part( $domain );

		return $result_local AND $result_domain;
	}

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

		$local_back = $local;

		/*
		 * Too much dots section
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
//		$start_with_dot = substr( $local, 0, 1 );
//		$end_with_dot   = substr( $local, -1, 1 );
//		if ( '.' == $start_with_dot || '.' == $end_with_dot ) {
		$dot_start_end = preg_match( '#(^\.|\.$)#', $local );
		if ( $dot_start_end ) {
			array_push( $this->whynot, 'Local part should not start or end with a dot. ' );
			return false;
		}

		/*
		 * Handle commented mail adresses
		 */
		if ( preg_match( '#\([^\)]+\)+#', $local ) ) {
			$local = $this->strip_comment( $local );
			if ( $local == $local_back ) {
				array_push( $this->whynot, 'Local part contains comment at wrong place.' );
				return false;
			}
		}

		/*
		 * Handle invalid characters
		 * "Without quotes, local-parts may consist of any combination of alphabetic characters, digits,
		 * or any of the special characters ! # $ % & ' * + - / = ?  ^ _ ` . { | } ~"
		 */
		$invalid     = preg_replace( '#[a-zA-z0-9\.\!\#\$\%\&\'\*\+\-\/\=\?\^\_`\{\|\}\~]+#', '', $local );
		$is_enclosed = preg_match( '#^\"([^"]+)\"#', $local, $enclosed_parts );
		if ( empty( $enclosed_parts ) && ! empty( $invalid ) ) {

			$is_valid = $this->handle_special_chars_in_local_part( $local, $is_enclosed );

			if ( ! $is_valid ) {
				array_push( $this->whynot, 'Local part contains not allowed characters.');
				return false;
			}
		}


		return true;
	}

	/**
	 * Validate the domain part of an email address
	 * @param	string	$domain	Domain part of an email address
	 * @return	boolean			True if the domain part is valid
	 */
	public function validate_domain_part( $domain ) {
		if ( 255 < strlen( $domain ) ) {
			array_push( $this->whynot, 'Domain part is too long, it contains more than 255 characters' );
			return false;
		}

		$labels = explode( '.', $domain );
		$label_too_long = false;
		array_walk( $labels, function($label)use(&$label_too_long){if(63<strlen($label))$label_too_long=$label;} );
		if ( $label_too_long ) {
			array_push( $this->whynot, printf( 'Domain contains a too long label (%s[&hellip;])', substr( $label_too_long, 0, 17 ) ) );
			return false;
		}

		$domain_back = $domain;

		// handle commented mail adresses
		if ( preg_match( '#\([^\)]+\)+#', $domain ) ) {
			$domain = $this->strip_comment( $domain );
			if ( $domain == $domain_back ){
				array_push( $this->whynot, 'Domain part contains comment at wrong place.' );
				return false;
			}
		}

		// matches IPv4 and IPv6, IPv6v4 (full and compressed)
		$ip = preg_match( '#^\[(?>(?>([a-f0-9]{1,4})(?>:(?1)){7}|(?!(?:.*[a-f0-9](?>:|$)){8,})((?1)(?>:(?1)){0,6})?::(?2)?)|(?>(?>(?1)(?>:(?1)){5}:|(?!(?:.*[a-f0-9]:){6,})(?3)?::(?>((?1)(?>:(?1)){0,4}):)?)?(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])(?>\.(?4)){3}))\]$#i', $domain );
		if ( $ip ) {
			// plain and well formatted ip-address
			return true;
		}

		$dot_period = preg_match( '#\.{2,}#', $domain );
		if ( $dot_period ) {
			array_push( $this->whynot, 'Domain part contains a dot period' );
			return false;
		}

		$dot_at_start_or_end = preg_match( '#(^\.|\.$)#', $domain );
		if ( $dot_at_start_or_end ) {
			array_push( $this->whynot, 'Domain part starts and/or end with a dot' );
			return false;
		}

		$hyphen_at_start_or_end = preg_match( '#(^\-|\-$)#', $domain );
		if ( $hyphen_at_start_or_end ) {
			array_push( $this->whynot, 'Domain part starts and/or end with a hyphen<br>' );
			return false;
		}

		$invalid_chars = preg_replace( '#[a-zA-Z0-9\.\-]#', '', $domain );
		if ( ! empty( $invalid_chars ) ) {
			array_push( $this->whynot, 'Domain part contains invalid characters, try to convert to punycode...' );

			require_once '/IDNA2.php';
			$idn = new NET_IDNA2();
			try {
				$idn_domain = $idn->encode( $domain );
			} catch (Exception $e) {
				array_push( $this->whynot, 'Cannot convert to punycode: '. $e->getMessage() );
				return false;
			}

			// test the punycode domain again
			$idn_invalid_chars = preg_replace( '#[a-zA-Z0-9\.\-]#', '', $idn_domain );

			if ( ! empty( $idn_invalid_chars ) ) {
				array_push( $this->whynot, 'The punycode version of the domain part still contains invalid characters' );
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
			// strip all escaped special chars
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

		// has no specail chars, either in an enqouted or non enqouted local part
		return true;
	}

	/**
	 * Strip comments enclosed in parenthesis from string
	 * @param	string	$string	String with comment enclosed in parenthesis
	 * @return	string	$string	String without comment
	 */
	public function strip_comment( $string ) {
		return preg_replace( sprintf( '#(^%1$s|%1$s$)#', '\([^\)]+\)' ), '', $string );
	}

}