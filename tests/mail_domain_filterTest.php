<?php

require_once dirname( dirname( __FILE__ ) ) . '\mail_domain_filter.php';

/**
 * Test class for Mail_Domain_Filter.
 * Generated by PHPUnit on 2013-05-21 at 11:06:54.
 */
class Mail_Domain_FilterTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Mail_Domain_Filter
	 */
	protected $domain;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		$this->domain = new Mail_Domain_Filter;
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() {}

	/**
	 * @covers Mail::validate_domain_part()
	 * @dataProvider domain_DataProvider
	 * @param	string	$domain
	 * @param	boolean	$expected
	 */
	public function testValidate_domain_part( $domain, $expected ) {
		$actual = $this->domain->validate_domain_part( $domain );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * DataProvider for valiadate and sanitize domain
	 * @return array
	 */
	public function domain_DataProvider() {
		return array(
			array( '(commentedemail)example.com',  true ), // comment at start of domain
			array( 'example.com(commentedemail)',  true ), // comment at end of domain
			array( '(commented email)example.com', true ),
			array( 'example.com(commented email)', true ),

			array( 'example.org', true ),
			array( 'localhost', true ),
			array( '[192.168.0.1]', true ),
			array( '[192.bad.ip.001]', false ),
			array( '[2001:2001:db8:1ff::a0b:dbd0]', true ),
			array( '[2001:0db8:85a3:08d3:1319:8a2e:0370:7344]', true ),
			array( '[2001:0DB8:85A3:08D3:1319:8A2E:0370:7344/128]', false ),
			array( '[2001:0DB8:85A3:0:1319:0:0370:7344]', true ),
//TODO: compressed IPv6 array( '[2001:0DB8:85A3::1319::0370:7344]', true );
			array( 'dotdot..org', false ),
			array( '.dot.start.com', false ),
			array( 'dot.end.com.', false ),
			array( '.dot.start.and.end.', false ),
			array( '-start-with-hyphen.com', false ),
			array( 'hyphen-at-end.org-', false ),
			array( '-hyphen-both-sides-', false ),
			array( 'to.longlable123456789012345678901234567890123456789012345678901234567890.long.lable', false),
			array( 'böse-straße.de', true ),
			array( 'ganz_böse_straße?.de', false ),
			array( 'bisschen-böse-straße?.de', false ),
		);
	}
	}

