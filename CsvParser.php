<?php
/**
 * Volcanus libraries for PHP
 *
 * @copyright 2011-2013 k-holy <k.holy74@gmail.com>
 * @license The MIT License (MIT)
 */

namespace Volcanus\CsvParser;

/**
 * CSV Parser for SplFileObject
 *
 * @author k.holy74@gmail.com
 */
class CsvParser implements \ArrayAccess
{

	/**
	 * @var string レコードの文字列を保持するバッファ
	 */
	private $buffer;

	/**
	 * @var Configuration 設定値のコレクション
	 */
	private $config;

	/**
	 * コンストラクタ
	 *
	 * @param array | \Traversable 設定オプション
	 */
	public function __construct($configurations = array())
	{
		$this->initialize($configurations);
	}

	/**
	 * 設定オプションおよびプロパティを初期化します。
	 *
	 * @param array | \Traversable 設定オプション
	 * @return $this
	 */
	public function initialize($configurations = array())
	{
		$this->buffer = '';
		$this->config = new Configuration(array(
			'delimiter'      => ',',
			'enclosure'      => '"',
			'escape'         => '"',
			'inputEncoding'  => null,
			'outputEncoding' => null,
			'sanitizing'     => false,
		));
		if (!empty($configurations)) {
			$this->config->attributes($configurations);
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
	 *
	 * @param string 設定名
	 * @return mixed 設定値 または $this
	 */
	public function config($name)
	{
		switch (func_num_args()) {
		case 1:
			return $this->config->get($name);
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
				$this->config->set($name, $value);
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
	 * @param string 1行分の文字列
	 * @return bool 1レコードの取得が終了したかどうか
	 */
	public function parse($line)
	{
		if ($this->config->get('sanitizing')) {
			$line = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]|\xC2[\x80-\x9F]/S', '', $line);
		}
		$outputEncoding = $this->config->get('outputEncoding');
		$inputEncoding = $this->config->get('inputEncoding');
		if (isset($outputEncoding)) {
			if (!isset($inputEncoding)) {
				$line = mb_convert_encoding($line, $outputEncoding, 'auto');
			} elseif (strcmp($outputEncoding, $inputEncoding) !== 0) {
				$line = mb_convert_encoding($line, $outputEncoding, $inputEncoding);
			}
		}
		$this->buffer .= $line;
		return (substr_count($this->buffer, $this->config->get('enclosure')) % 2 === 0);
	}

	/**
	 * バッファに保持しているレコードの文字列を返し、空にします。
	 *
	 * @return string レコードの文字列
	 */
	public function getBuffer()
	{
		$buffer = $this->buffer;
		$this->clear();
		return $buffer;
	}

	/**
	 * バッファを空にします。
	 */
	public function clear()
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
	 * @param string 1レコード分の文字列
	 * @return mixed 1レコード分の配列 または NULL
	 */
	public function convert($record)
	{

		$delimiter = $this->config->get('delimiter');
		$enclosure = $this->config->get('enclosure');
		$escape = $this->config->get('escape');

		// 行末の復帰・改行を削除し、空の場合はNULLを返す
		$record = rtrim($record, "\x0A\x0D");
		if (strlen($record) === 0) {
			return null;
		}

		// 正規表現パターン簡略化のためデリミタを付与
		$record = preg_replace('/(?:\x0D\x0A|[\x0D\x0A])?$/', $delimiter, $record);

		$delimiter_quoted = preg_quote($delimiter);
		$enclosure_quoted = preg_quote($enclosure);
		$escape_quoted    = preg_quote($escape);

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

		$fields = array();
		foreach ($matches[1] as $value) {
			$fields[] = str_replace($escape . $enclosure, $enclosure, preg_replace($field_pattern, '$1', $value));
		}

		return $fields;
	}

	/**
	 * magic getter
	 *
	 * @param string 設定名
	 */
	public function __get($name)
	{
		if (method_exists($this, 'get' . ucfirst($name))) {
			return $this->{'get' . ucfirst($name)}();
		}
		return $this->config->get($name);
	}

	/**
	 * magic setter
	 *
	 * @throws \RuntimeException
	 */
	public function __set($name, $value)
	{
		throw new \RuntimeException(
			sprintf('The property "%s" is read only.', $name)
		);
	}

	/**
	 * ArrayAccess::offsetExists()
	 *
	 * @param mixed
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		if (method_exists($this, 'get' . ucfirst($offset))) {
			return !is_null($this->{'get' . ucfirst($offset)}());
		}
		return $this->config->has($offset);
	}

	/**
	 * ArrayAccess::offsetGet()
	 *
	 * @param mixed
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		if (method_exists($this, 'get' . ucfirst($offset))) {
			return $this->{'get' . ucfirst($offset)}();
		}
		return $this->config->get($offset);
	}

	/**
	 * ArrayAccess::offsetSet()
	 *
	 * @throws \RuntimeException
	 */
	public function offsetSet($offset, $value)
	{
		throw new \RuntimeException(
			sprintf('The key "%s" could not set.', $offset)
		);
	}

	/**
	 * ArrayAccess::offsetUnset()
	 *
	 * @throws \RuntimeException
	 */
	public function offsetUnset($offset)
	{
		throw new \RuntimeException(
			sprintf('The key "%s" could not unset.', $offset)
		);
	}

}
