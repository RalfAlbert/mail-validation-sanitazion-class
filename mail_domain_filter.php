<?php
class Mail_Domain_Filter
{
	public $whynot = array();

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
			$domain = preg_replace( sprintf( '#(^%1$s|%1$s$)#', '\([^\)]+\)' ), '', $domain );
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

}