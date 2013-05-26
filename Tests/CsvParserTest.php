<?php
/**
 * Volcanus libraries for PHP
 *
 * @copyright 2011-2013 k-holy <k.holy74@gmail.com>
 * @license The MIT License (MIT)
 */

namespace Volcanus\CsvParser\Tests;

use Volcanus\CsvParser\CsvParser;

/**
 * CsvParserTest
 *
 * @author k.holy74@gmail.com
 */
class CsvParserTest extends \PHPUnit_Framework_TestCase
{

	public function testDefaultConfigParameter()
	{
		$parser = new CsvParser();
		$this->assertEquals(',', $parser->config('delimiter'));
		$this->assertEquals('"', $parser->config('enclosure'));
		$this->assertEquals('"', $parser->config('escape'));
		$this->assertEquals(mb_internal_encoding(), $parser->config('inputEncoding'));
		$this->assertEquals(mb_internal_encoding(), $parser->config('outputEncoding'));
	}

	public function testConstructWithConfigParameters()
	{
		$parser = new CsvParser(array(
			'delimiter'      => "\t",
			'enclosure'      => "'",
			'escape'         => '\\',
			'inputEncoding'  => 'SJIS-win',
			'outputEncoding' => 'EUC-JP',
		));
		$this->assertEquals("\t", $parser->config('delimiter'));
		$this->assertEquals("'" , $parser->config('enclosure'));
		$this->assertEquals('\\', $parser->config('escape'));
		$this->assertEquals('SJIS-win', $parser->config('inputEncoding'));
		$this->assertEquals('EUC-JP', $parser->config('outputEncoding'));
	}

	public function testGetConfigByPropertyAccess()
	{
		$parser = new CsvParser(array(
			'delimiter'      => "\t",
			'enclosure'      => "'",
			'escape'         => '\\',
			'inputEncoding'  => 'SJIS-win',
			'outputEncoding' => 'EUC-JP',
		));
		$this->assertEquals($parser->config('delimiter'), $parser->delimiter);
		$this->assertEquals($parser->config('enclosure'), $parser->enclosure);
		$this->assertEquals($parser->config('escape'), $parser->escape);
		$this->assertEquals($parser->config('inputEncoding'), $parser->inputEncoding);
		$this->assertEquals($parser->config('outputEncoding'), $parser->outputEncoding);
	}

	public function testGetConfigByArrayAccess()
	{
		$parser = new CsvParser(array(
			'delimiter'      => "\t",
			'enclosure'      => "'",
			'escape'         => '\\',
			'inputEncoding'  => 'SJIS-win',
			'outputEncoding' => 'EUC-JP',
		));
		$this->assertEquals($parser->config('delimiter'), $parser['delimiter']);
		$this->assertEquals($parser->config('enclosure'), $parser['enclosure']);
		$this->assertEquals($parser->config('escape'), $parser['escape']);
		$this->assertEquals($parser->config('inputEncoding'), $parser['inputEncoding']);
		$this->assertEquals($parser->config('outputEncoding'), $parser['outputEncoding']);
	}

	public function testParseALineThenConvert()
	{
		$parser = new CsvParser(array(
			'delimiter'      => ',',
			'enclosure'      => '"',
			'escape'         => '"',
			'inputEncoding'  => 'UTF-8',
			'outputEncoding' => 'UTF-8',
		));
		$this->assertTrue($parser->parse('1,田中' . "\r\n"));
		$this->assertEquals(array('1', '田中'),
			$parser->convert($parser->getBuffer())
		);
	}

	public function testParseSomeLinesThenConvert()
	{
		$parser = new CsvParser(array(
			'delimiter'      => ',',
			'enclosure'      => '"',
			'escape'         => '"',
			'inputEncoding'  => 'UTF-8',
			'outputEncoding' => 'UTF-8',
		));
		$this->assertFalse($parser->parse('1,"田中' . "\r\n"));
		$this->assertFalse($parser->parse("\r\n"));
		$this->assertTrue($parser->parse('"' . "\r\n"));
		$this->assertEquals(array('1', "田中\r\n\r\n"),
			$parser->convert($parser->getBuffer())
		);
	}

	public function testConvertEncloseDelimiter()
	{
		$parser = new CsvParser(array(
			'delimiter'      => ',',
			'enclosure'      => '"',
			'escape'         => '"',
			'inputEncoding'  => 'UTF-8',
			'outputEncoding' => 'UTF-8',
		));
		$this->assertEquals(array('1', '田中,'),
			$parser->convert('1,"田中,"')
		);
	}

	public function testConvertEscapedEnclosure()
	{
		$parser = new CsvParser(array(
			'delimiter'      => ',',
			'enclosure'      => '"',
			'escape'         => '"',
			'inputEncoding'  => 'UTF-8',
			'outputEncoding' => 'UTF-8',
		));
		$this->assertEquals(array('1', '田中"'),
			$parser->convert('1,"田中"""')
		);
	}

	public function testConvertEnclosedCarriageReturnAndLineFeed()
	{
		$parser = new CsvParser(array(
			'delimiter'      => ',',
			'enclosure'      => '"',
			'escape'         => '"',
			'inputEncoding'  => 'UTF-8',
			'outputEncoding' => 'UTF-8',
		));
		$this->assertEquals(array('1', "田中\r\n"),
			$parser->convert("1,\"田中\r\n\"")
		);
	}

	public function testConvertOnlySpaceField()
	{
		$parser = new CsvParser(array(
			'delimiter'      => ',',
			'enclosure'      => '"',
			'escape'         => '"',
			'inputEncoding'  => 'UTF-8',
			'outputEncoding' => 'UTF-8',
		));
		$this->assertEquals(array(' ', '1', '田中'),
			$parser->convert(' ,1,田中')
		);
	}

	public function testConvertNotClosedEnclosure()
	{
		$parser = new CsvParser(array(
			'delimiter'      => ',',
			'enclosure'      => '"',
			'escape'         => '"',
			'inputEncoding'  => 'UTF-8',
			'outputEncoding' => 'UTF-8',
		));
		$this->assertEquals(array('"', '1', '田中'),
			$parser->convert('",1,田中')
		);
	}

	public function testConvertNotOpenedEnclosure()
	{
		$parser = new CsvParser(array(
			'delimiter'      => ',',
			'enclosure'      => '"',
			'escape'         => '"',
			'inputEncoding'  => 'UTF-8',
			'outputEncoding' => 'UTF-8',
		));
		$this->assertEquals(array('', '"', '1'),
			$parser->convert(',","1"')
		);
	}

	public function testConvertNotClosedEnclosureAndSpaceBeforeDelimiter()
	{
		$parser = new CsvParser(array(
			'delimiter'      => ',',
			'enclosure'      => '"',
			'escape'         => '"',
			'inputEncoding'  => 'UTF-8',
			'outputEncoding' => 'UTF-8',
		));
		$this->assertEquals(array('" ', '"1" ', '田中"'),
			$parser->convert('" ,"1" ,田中""')
		);
	}

	public function testConvertNotClosedEnclosureAndSpaceAfterDelimiter()
	{
		$parser = new CsvParser(array(
			'delimiter'      => ',',
			'enclosure'      => '"',
			'escape'         => '"',
			'inputEncoding'  => 'UTF-8',
			'outputEncoding' => 'UTF-8',
		));
		$this->assertEquals(array('"', ' "1"', ' "田中"'),
			$parser->convert('", "1", "田中""')
		);
	}

	public function testConvertTabSeparatedValues()
	{
		$parser = new CsvParser(array(
			'delimiter'      => "\t",
			'enclosure'      => '"',
			'escape'         => '\\',
			'inputEncoding'  => 'UTF-8',
			'outputEncoding' => 'UTF-8',
		));
		$this->assertEquals(array('1', '田中'),
			$parser->convert('1' . "\t" . '"田中"')
		);
	}

	public function testConvertTabSeparatedValuesAndEscapedEnclosure()
	{
		$parser = new CsvParser(array(
			'delimiter'      => "\t",
			'enclosure'      => '"',
			'escape'         => '\\',
			'inputEncoding'  => 'UTF-8',
			'outputEncoding' => 'UTF-8',
		));
		$this->assertEquals(array('1', '田中"'),
			$parser->convert('1' . "\t" . '"田中\\""')
		);
	}

}
