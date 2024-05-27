<?php
/**
 * Volcanus libraries for PHP 8.1~
 *
 * @copyright k-holy <k.holy74@gmail.com>
 * @license The MIT License (MIT)
 */

namespace Volcanus\CsvParser\Test;

use PHPUnit\Framework\TestCase;
use Volcanus\CsvParser\CsvParser;

/**
 * CsvParserTest
 *
 * @author k.holy74@gmail.com
 */
class CsvParserTest extends TestCase
{

    public function testDefaultConfigParameter()
    {
        $parser = new CsvParser();
        $this->assertEquals(',', $parser->config('delimiter'));
        $this->assertEquals('"', $parser->config('enclosure'));
        $this->assertEquals('"', $parser->config('escape'));
        $this->assertNull($parser->config('inputEncoding'));
        $this->assertNull($parser->config('outputEncoding'));
        $this->assertFalse($parser->config('sanitizing'));
        $this->assertFalse($parser->config('eraseBom'));
    }

    public function testConstructWithConfigParameters()
    {
        $parser = new CsvParser([
            'delimiter' => "\t",
            'enclosure' => "'",
            'escape' => '\\',
            'inputEncoding' => 'SJIS-win',
            'outputEncoding' => 'EUC-JP',
            'sanitizing' => true,
            'eraseBom' => true,
        ]);
        $this->assertEquals("\t", $parser->config('delimiter'));
        $this->assertEquals("'", $parser->config('enclosure'));
        $this->assertEquals('\\', $parser->config('escape'));
        $this->assertEquals('SJIS-win', $parser->config('inputEncoding'));
        $this->assertEquals('EUC-JP', $parser->config('outputEncoding'));
        $this->assertTrue($parser->config('sanitizing'));
        $this->assertTrue($parser->config('eraseBom'));
    }

    public function testGetConfigByProperty()
    {
        $parser = new CsvParser([
            'delimiter' => "\t",
            'enclosure' => "'",
            'escape' => '\\',
            'inputEncoding' => 'SJIS-win',
            'outputEncoding' => 'EUC-JP',
            'sanitizing' => true,
            'eraseBom' => true,
        ]);
        $this->assertEquals($parser->config('delimiter'), $parser->delimiter);
        $this->assertEquals($parser->config('enclosure'), $parser->enclosure);
        $this->assertEquals($parser->config('escape'), $parser->escape);
        $this->assertEquals($parser->config('inputEncoding'), $parser->inputEncoding);
        $this->assertEquals($parser->config('outputEncoding'), $parser->outputEncoding);
        $this->assertEquals($parser->config('sanitizing'), $parser->sanitizing);
        $this->assertEquals($parser->config('eraseBom'), $parser->eraseBom);
    }

    public function testRaiseExceptionWhenSetConfigByProperty()
    {
        $this->expectException(\RuntimeException::class);
        $parser = new CsvParser();
        $parser->delimiter = "\t";
    }

    public function testGetConfigByOffsetGet()
    {
        $parser = new CsvParser([
            'delimiter' => "\t",
            'enclosure' => "'",
            'escape' => '\\',
            'inputEncoding' => 'SJIS-win',
            'outputEncoding' => 'EUC-JP',
            'sanitizing' => true,
            'eraseBom' => true,
        ]);
        $this->assertEquals($parser->config('delimiter'), $parser['delimiter']);
        $this->assertEquals($parser->config('enclosure'), $parser['enclosure']);
        $this->assertEquals($parser->config('escape'), $parser['escape']);
        $this->assertEquals($parser->config('inputEncoding'), $parser['inputEncoding']);
        $this->assertEquals($parser->config('outputEncoding'), $parser['outputEncoding']);
        $this->assertEquals($parser->config('sanitizing'), $parser['sanitizing']);
        $this->assertEquals($parser->config('eraseBom'), $parser['eraseBom']);
    }

    public function testRaiseExceptionWhenSetConfigByOffsetSet()
    {
        $this->expectException(\RuntimeException::class);
        $parser = new CsvParser();
        $parser['delimiter'] = "\t";
    }

    public function testRaiseExceptionWhenUnsetConfigByArrayAccess()
    {
        $this->expectException(\RuntimeException::class);
        $parser = new CsvParser();
        unset($parser['delimiter']);
    }

    public function testParse()
    {
        $parser = new CsvParser();
        $this->assertTrue($parser->parse('1,田中' . "\r\n"));
    }

    public function testGetBuffer()
    {
        $parser = new CsvParser();
        $this->assertTrue($parser->parse('1,田中' . "\r\n"));
        $this->assertEquals('1,田中' . "\r\n", $parser->getBuffer());
    }

    public function testGetBufferByProperty()
    {
        $parser = new CsvParser();
        $this->assertTrue($parser->parse('1,田中' . "\r\n"));
        $this->assertEquals('1,田中' . "\r\n", $parser->buffer);
    }

    public function testGetBufferByOffsetGet()
    {
        $parser = new CsvParser();
        $this->assertTrue($parser->parse('1,田中' . "\r\n"));
        $this->assertEquals('1,田中' . "\r\n", $parser['buffer']);
    }

    public function testIssetBufferByOffsetExists()
    {
        $parser = new CsvParser();
        $this->assertTrue($parser->parse('1,田中' . "\r\n"));
        $this->assertTrue(isset($parser['buffer']));
    }

    public function testConvert()
    {
        $parser = new CsvParser();
        $this->assertEquals(['1', '田中'],
            $parser->convert('1,田中' . "\r\n")
        );
    }

    public function testParseSomeLines()
    {
        $parser = new CsvParser();
        $this->assertFalse($parser->parse('1,"田中' . "\r\n"));
        $this->assertFalse($parser->parse("\r\n"));
        $this->assertTrue($parser->parse('"' . "\r\n"));
        $this->assertEquals(['1', "田中\r\n\r\n"],
            $parser->convert($parser->getBuffer())
        );
    }

    public function testParseWithConvertEncoding()
    {
        $parser = new CsvParser([
            'inputEncoding' => 'SJIS',
            'outputEncoding' => 'UTF-8',
        ]);
        $this->assertTrue($parser->parse(mb_convert_encoding('1,ソ十貼能表暴予' . "\r\n", 'SJIS', 'UTF-8')));
        $this->assertEquals(['1', 'ソ十貼能表暴予'],
            $parser->convert($parser->getBuffer())
        );
    }

    public function testParseWithSanitizing()
    {
        $parser = new CsvParser([
            'sanitizing' => true,
        ]);
        $this->assertTrue($parser->parse("1,田中\0\0" . "\r\n"));
        $this->assertEquals(['1', '田中'],
            $parser->convert($parser->getBuffer())
        );
    }

    public function testParseWithEraseBomUtf8()
    {
        $parser = new CsvParser([
            'inputEncoding' => CsvParser::ENCODING_UTF8,
            'eraseBom' => true,
        ]);
        $this->assertTrue($parser->parse("\xEF\xBB\xBF1,田中" . "\r\n"));
        $this->assertEquals(['1', '田中'],
            $parser->convert($parser->getBuffer())
        );
    }

    public function testParseWithEraseBomUtf16le()
    {
        $parser = new CsvParser([
            'inputEncoding' => CsvParser::ENCODING_UTF16LE,
            'eraseBom' => true,
        ]);
        $this->assertTrue($parser->parse("\xFE\xFF1,田中" . "\r\n"));
        $this->assertEquals(['1', '田中'],
            $parser->convert($parser->getBuffer())
        );
    }

    public function testParseWithEraseBomUtf16be()
    {
        $parser = new CsvParser([
            'inputEncoding' => CsvParser::ENCODING_UTF16BE,
            'eraseBom' => true,
        ]);
        $this->assertTrue($parser->parse("\xFF\xFE1,田中" . "\r\n"));
        $this->assertEquals(['1', '田中'],
            $parser->convert($parser->getBuffer())
        );
    }

    public function testConvertReturnNullWhenEmptyLine()
    {
        $parser = new CsvParser();
        $this->assertNull($parser->convert("\r\n"));
    }

    public function testConvertEncloseDelimiter()
    {
        $parser = new CsvParser();
        $this->assertEquals(['1', '田中,'],
            $parser->convert('1,"田中,"')
        );
    }

    public function testConvertEscapedEnclosure()
    {
        $parser = new CsvParser();
        $this->assertEquals(['1', '田中"'],
            $parser->convert('1,"田中"""')
        );
    }

    public function testConvertEnclosedCarriageReturnAndLineFeed()
    {
        $parser = new CsvParser();
        $this->assertEquals(['1', "田中\r\n"],
            $parser->convert("1,\"田中\r\n\"")
        );
    }

    public function testConvertOnlySpaceField()
    {
        $parser = new CsvParser();
        $this->assertEquals([' ', '1', '田中'],
            $parser->convert(' ,1,田中')
        );
    }

    public function testConvertNotClosedEnclosure()
    {
        $parser = new CsvParser();
        $this->assertEquals(['"', '1', '田中'],
            $parser->convert('",1,田中')
        );
    }

    public function testConvertNotOpenedEnclosure()
    {
        $parser = new CsvParser();
        $this->assertEquals(['', '"', '1'],
            $parser->convert(',","1"')
        );
    }

    public function testConvertNotClosedEnclosureAndSpaceBeforeDelimiter()
    {
        $parser = new CsvParser();
        $this->assertEquals(['" ', '"1" ', '田中"'],
            $parser->convert('" ,"1" ,田中""')
        );
    }

    public function testConvertNotClosedEnclosureAndSpaceAfterDelimiter()
    {
        $parser = new CsvParser();
        $this->assertEquals(['"', ' "1"', ' "田中"'],
            $parser->convert('", "1", "田中""')
        );
    }

    public function testConvertTabSeparatedValues()
    {
        $parser = new CsvParser([
            'delimiter' => "\t",
            'enclosure' => '"',
            'escape' => '\\',
        ]);
        $this->assertEquals(['1', '田中'],
            $parser->convert('1' . "\t" . '"田中"')
        );
    }

    public function testConvertTabSeparatedValuesAndEscapedEnclosure()
    {
        $parser = new CsvParser([
            'delimiter' => "\t",
            'enclosure' => '"',
            'escape' => '\\',
        ]);
        $this->assertEquals(['1', '田中"'],
            $parser->convert('1' . "\t" . '"田中\\""')
        );
    }

    public function testCsvParserWithSplFileObject()
    {
        $parser = new CsvParser([
            'inputEncoding' => 'SJIS',
            'outputEncoding' => 'UTF-8',
        ]);

        $csvFile = new \SplFileObject('php://temp', '+r');
        $csvFile->fwrite(mb_convert_encoding("1,田中\r\n", 'SJIS', 'UTF-8'));
        $csvFile->fwrite(mb_convert_encoding("2,山田\r\n", 'SJIS', 'UTF-8'));
        $csvFile->fwrite(mb_convert_encoding("3,\"鈴木\r\n\"\r\n", 'SJIS', 'UTF-8')); // 復帰・改行を含むレコード
        $csvFile->rewind();

        $users = [];

        foreach ($csvFile as $line) {

            // 1件分のレコード取得が終了するまで各行をパース
            if (!$parser->parse($line)) {
                continue;
            }

            $csv = $parser->getBuffer();

            $row = $parser->convert($csv);

            // 空行にはNULLが返されるので無視
            if (is_null($row)) {
                continue;
            }

            // CSVのフィールドをオブジェクトに取得
            $user = new \stdClass();
            $user->id = $row[0];
            $user->name = $row[1];

            $users[] = $user;
        }

        $this->assertCount(3, $users);
        $this->assertEquals('1', $users[0]->id);
        $this->assertEquals('田中', $users[0]->name);
        $this->assertEquals('2', $users[1]->id);
        $this->assertEquals('山田', $users[1]->name);
        $this->assertEquals('3', $users[2]->id);
        $this->assertEquals("鈴木\r\n", $users[2]->name);
    }

}
