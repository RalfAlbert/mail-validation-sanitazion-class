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

require_once 'mail_local_filter.php';
require_once 'mail_domain_filter.php';

class Mail
{
	public $whynot = array();
	public $mail   = '';

	private $local_filter = null;
	private $domain_filter = null;

	/**
	 * Constructor
	 * @param	string	$mail	Email address to be validated or sanitized
	 */
	public function __construct( $mail = '' ) {
		$this->mail          = $mail;
		$this->local_filter  = new Mail_Local_Filter();
		$this->domain_filter = new Mail_Domain_Filter();
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
		$result_local  = $this->local_filter->validate_local_part( $local );
		if ( ! $result_local )
			array_merge( $this->whynot, $this->local_filter->whynot );

		$result_domain = $this->domain_filter->validate_domain_part( $domain );
		if ( ! $result_domain )
			array_merge( $this->whynot, $this->domain_filter->whynot );

		return $result_local AND $result_domain;
	}

}