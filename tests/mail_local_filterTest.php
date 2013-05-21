<?php

require_once dirname( dirname( __FILE__ ) ) . '\mail_local_filter.php';

/**
 * Test class for Mail_Local_Filter.
 * Generated by PHPUnit on 2013-05-21 at 10:45:53.
 */
class Mail_Local_FilterTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Mail_Local_Filter
	 */
	protected $local;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		$this->local = new Mail_Local_Filter;
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() {}

	/**
	 * @covers Mail_Filter_Local::validate_local_part()
	 * @dataProvider local_DataProvider
	 * @param	string	$local
	 * @param	boolean	$expected
	 * @param	string	$sanitized
	 */
	public function testValidate_local_part( $local, $expected, $sanitized ) {
		$actual = $this->local->validate_local_part( $local );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * DataProvider for validate and sanitize local part
	 * @return	array
	 */
	public function local_DataProvider() {
		return array(
			array( 'niceandsimple',                           true, 'niceandsimple' ),
			array( 'very.common',                             true, 'very.common' ),
			array( 'user',                                    true, 'user' ),
			array( '"much.more unusual"',                     true, '"much.more unusual"' ),
			array( '"very.unusual.@.unusual.com"',            true, '"very.unusual.@.unusual.com"' ),
			array( 'postbox',                                 true, 'postbox' ),
			array( 'admin',                                   true, 'admin' ),
			array( '!#$%&\'*+-/=?^_`{}|~',                    true, '!#$%&\'*+-/=?^_`{}|~' ),
			array( 'john@smith',                              false, 'john\@smith@example.com' ),
			array( 'john\@smith',                             true, 'john\@smith@example.com' ),
			array( '" "',                                     true, '" "' ), //space between the quotes
			array( '"()<>[]:,;@\\\"!#$%&\'*+-/=?^_`{}| ~.a"', true, '"()<>[]:,;@\\\"!#$%&\'*+-/=?^_`{}| ~.a"' ),
			array( 'a.little.lengthy.but.fine',               true, 'a.little.lengthy.but.fine' ),
			array( 'disposable.style.email.with+symbol',      true, 'disposable.style.email.with+symbol' ),
			array( '"very.(),:;<>[]\".VERY.\"very@\\ \"very\".unusual"', true, '"very.(),:;<>[]\".VERY.\"very@\\ \"very\".unusual"' ),

			// comments in local part
			array( 'jo.sm(commentedemail)',  true, 'jo.sm@example.com' ), // comment at start of local part
			array( '(commentedemail)jo.sm',  true, 'jo.sm@example.com' ), // comment at end of local part
			array( 'jo.sm(commented email)', true, 'jo.sm@example.com' ),
			array( '(commented email)jo.sm', true, 'jo.sm@example.com' ),

			// invalid, cannot be sanitized
			array( 'jo(commented email)sm',     false, '' ),
			array( 'A@b@c@',                    false, '' ),
			array( 'a"b(c)d,e:f;g<h>i[j\k]l',   false, '' ),
			array( 'just"not"right',            false, '' ),
			array( 'this is"not\allowed',       false, '' ),
			array( 'this\ still\"not\\allowed', false, '' ),
			array( 'too.longlable123456789012345678901234567890123456789012345678901234567890.long-lable', false, '' ),

		);
	}

}
?>