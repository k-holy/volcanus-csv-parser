<?php
/**
 * Volcanus libraries for PHP 8.1~
 *
 * @copyright k-holy <k.holy74@gmail.com>
 * @license The MIT License (MIT)
 */

namespace Volcanus\CsvParser;

/**
 * CSV Parser for SplFileObject
 *
 * @property $delimiter
 * @property $enclosure
 * @property $escape
 * @property $inputEncoding
 * @property $outputEncoding
 * @property $sanitizing
 * @property $eraseBom
 * @property $buffer
 *
 * @author k.holy74@gmail.com
 */
class CsvParser implements \ArrayAccess
{

    const BOM_UTF8 = "\xEF\xBB\xBF";
    const BOM_UTF16LE = "\xFE\xFF";
    const BOM_UTF16BE = "\xFF\xFE";
    const ENCODING_UTF8 = 'utf-8';
    const ENCODING_UTF16LE = 'utf-16le';
    const ENCODING_UTF16BE = 'utf-16';

    /**
     * @var string レコードの文字列を保持するバッファ
     */
    private string $buffer;

    /**
     * @var Configuration 設定値のコレクション
     */
    private Configuration $config;

    /**
     * コンストラクタ
     *
     * @param iterable $configurations 設定オプション
     */
    public function __construct(iterable $configurations = [])
    {
        $this->initialize($configurations);
    }

    /**
     * 設定オプションおよびプロパティを初期化します。
     *
     * @param iterable $configurations 設定オプション
     * @return self
     */
    public function initialize(iterable $configurations = []): self
    {
        $this->buffer = '';
        $this->config = new Configuration([
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '"',
            'inputEncoding' => null,
            'outputEncoding' => null,
            'sanitizing' => false,
            'eraseBom' => false,
        ]);
        if (!empty($configurations)) {
            foreach ($configurations as $name => $value) {
                $this->config->offsetSet($name, $value);
            }
        }
        return $this;
    }

    /**
     * 引数1の場合は指定された設定の値を返します。
     * 引数2の場合は指定された設置の値をセットして$thisを返します。
     *
     * delimiter      : フィールドの区切り文字 ※1文字のみ対応
     * enclosure      : フィールドの囲み文字 ※1文字のみ対応
     * escape         : フィールドに含まれる囲み文字のエスケープ文字 ※1文字のみ対応
     * inputEncoding  : 入力文字コード（CSVファイルの文字コード）
     * outputEncoding : 出力文字コード（データの文字コード）
     * sanitizing     : 復帰・改行・水平タブ・スペース以外の制御コード自動削除を有効にするか
     * eraseBom       : 1行目の読み込み時にBOMを消去するかどうか
     *
     * @param string $name 設定名
     * @return string|bool|int|null|self 設定値 または $this
     */
    public function config(string $name): string|bool|int|null|self
    {
        switch (func_num_args()) {
            case 1:
                return $this->config->offsetGet($name);
            case 2:
                $value = func_get_arg(1);
                if (isset($value)) {
                    switch ($name) {
                        case 'delimiter':
                        case 'enclosure':
                        case 'escape':
                            if (!is_string($value)) {
                                throw new \InvalidArgumentException(
                                    sprintf('The config parameter "%s" only accepts string.', $name)
                                );
                            }
                            if (strlen($value) > 1) {
                                throw new \InvalidArgumentException(
                                    sprintf('The config parameter "%s" accepts one character.', $name)
                                );
                            }
                            break;
                        case 'inputEncoding':
                        case 'outputEncoding':
                            if (!is_string($value)) {
                                throw new \InvalidArgumentException(
                                    sprintf('The config parameter "%s" only accepts string.', $name)
                                );
                            }
                            break;
                        case 'sanitizing':
                        case 'eraseBom':
                            if (!is_bool($value) && !is_int($value) && !ctype_digit($value)) {
                                throw new \InvalidArgumentException(
                                    sprintf('The config parameter "%s" only accepts bool.', $name));
                            }
                            $value = (bool)$value;
                            break;
                        default:
                            throw new \InvalidArgumentException(
                                sprintf('The config parameter "%s" is not defined.', $name)
                            );
                    }
                    $this->config->offsetSet($name, $value);
                }
                return $this;
        }
        throw new \InvalidArgumentException('Invalid argument count.');
    }

    /**
     * 1行分の文字列を取得し、まだ閉じられていない囲み文字があればFALSEを返します。
     *
     * サニタイジングが有効な場合、復帰・改行・水平タブ・スペース以外の制御コードを削除します。
     * 出力エンコーディングが指定されており、入力エンコーディングと異なる場合は変換します。
     * 参考 http://en.wikipedia.org/wiki/C0_and_C1_control_codes
     *
     * @param string $line 1行分の文字列
     * @return bool 1レコードの取得が終了したかどうか
     */
    public function parse(string $line): bool
    {
        if ($this->config->offsetGet('sanitizing')) {
            $line = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]|\xC2[\x80-\x9F]/S', '', $line);
        }
        $outputEncoding = $this->config->offsetGet('outputEncoding');
        $inputEncoding = $this->config->offsetGet('inputEncoding');
        if (strlen($this->buffer) === 0 && $this->config->offsetGet('eraseBom')) {
            $bom = null;
            switch (strtolower($inputEncoding)) {
                case self::ENCODING_UTF8:
                    $bom = self::BOM_UTF8;
                    break;
                case self::ENCODING_UTF16LE:
                    $bom = self::BOM_UTF16LE;
                    break;
                case self::ENCODING_UTF16BE:
                    $bom = self::BOM_UTF16BE;
                    break;
            }
            if ($bom !== null) {
                $line = preg_replace(sprintf('/^%s/', $bom), '', $line);
            }
        }
        if (isset($outputEncoding)) {
            if (!isset($inputEncoding)) {
                $line = mb_convert_encoding($line, $outputEncoding, 'auto');
            } elseif (strcmp($outputEncoding, $inputEncoding) !== 0) {
                $line = mb_convert_encoding($line, $outputEncoding, $inputEncoding);
            }
        }
        $this->buffer .= $line;
        return (substr_count($this->buffer, $this->config->offsetGet('enclosure')) % 2 === 0);
    }

    /**
     * バッファに保持しているレコードの文字列を返し、空にします。
     *
     * @return string レコードの文字列
     */
    public function getBuffer(): string
    {
        $buffer = $this->buffer;
        $this->clear();
        return $buffer;
    }

    /**
     * バッファを空にします。
     */
    public function clear(): void
    {
        $this->buffer = '';
    }

    /**
     * 1レコード分の文字列を配列に変換して返します。(PCRE正規表現版)
     * ただし、末尾が復帰・改行のみの文字列が渡された場合は、NULLを返します。
     *
     * 正規表現パターンは [Perlメモ] を参考
     * http://www.din.or.jp/~ohzaki/perl.htm#CSV2Values
     *
     * @param string $record 1レコード分の文字列
     * @return array|null 1レコード分の配列 または NULL
     */
    public function convert(string $record): ?array
    {

        $delimiter = $this->config->offsetGet('delimiter');
        $enclosure = $this->config->offsetGet('enclosure');
        $escape = $this->config->offsetGet('escape');

        // 行末の復帰・改行を削除し、空の場合はNULLを返す
        $record = rtrim($record, "\x0A\x0D");
        if (strlen($record) === 0) {
            return null;
        }

        // 正規表現パターン簡略化のためデリミタを付与
        $record = preg_replace('/(?:\x0D\x0A|[\x0D\x0A])?$/', $delimiter, $record);

        $delimiter_quoted = preg_quote($delimiter);
        $enclosure_quoted = preg_quote($enclosure);
        $escape_quoted = preg_quote($escape);

        preg_match_all(sprintf('/(%s[^%s]*(?:%s%s[^%s]*)*%s|[^%s]*)%s/',
            $enclosure_quoted,
            $enclosure_quoted,
            $escape_quoted,
            $enclosure_quoted,
            $enclosure_quoted,
            $enclosure_quoted,
            $delimiter_quoted,
            $delimiter_quoted
        ), $record, $matches);

        $field_pattern = sprintf('/^%s(.*)%s$/s', $enclosure_quoted, $enclosure_quoted);

        $fields = [];
        foreach ($matches[1] as $value) {
            $fields[] = str_replace($escape . $enclosure, $enclosure, preg_replace($field_pattern, '$1', $value));
        }

        return $fields;
    }

    /**
     * magic getter
     *
     * @param string $name 設定名
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        if (method_exists($this, 'get' . ucfirst($name))) {
            return $this->{'get' . ucfirst($name)}();
        }
        return $this->config->offsetGet($name);
    }

    /**
     * magic setter
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, mixed $value): void
    {
        throw new \RuntimeException(
            sprintf('The property "%s" is read only.', $name)
        );
    }

    /**
     * ArrayAccess::offsetExists()
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        if (method_exists($this, 'get' . ucfirst($offset))) {
            return !is_null($this->{'get' . ucfirst($offset)}());
        }
        return $this->config->offsetExists($offset);
    }

    /**
     * ArrayAccess::offsetGet()
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (method_exists($this, 'get' . ucfirst($offset))) {
            return $this->{'get' . ucfirst($offset)}();
        }
        return $this->config->offsetGet($offset);
    }

    /**
     * ArrayAccess::offsetSet()
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \RuntimeException(
            sprintf('The key "%s" could not set.', $offset)
        );
    }

    /**
     * ArrayAccess::offsetUnset()
     *
     * @param mixed $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new \RuntimeException(
            sprintf('The key "%s" could not unset.', $offset)
        );
    }

}
