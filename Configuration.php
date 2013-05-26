<?php
/**
 * Volcanus libraries for PHP
 *
 * @copyright 2011-2013 k-holy <k.holy74@gmail.com>
 * @license The MIT License (MIT)
 */

namespace Volcanus\CsvParser;

/**
 * Configuration
 *
 * @author k.holy74@gmail.com
 */
class Configuration implements \ArrayAccess, \IteratorAggregate, \Countable
{

	/**
	 * @var array 属性の配列
	 */
	private $attributes;

	/**
	 * コンストラクタ
	 *
	 * @param array 属性の配列
	 */
	public function __construct($attributes = array())
	{
		$this->initialize($attributes);
	}

	/**
	 * 属性を初期化します。
	 *
	 * @param array 属性の配列
	 * @return $this
	 */
	public function initialize($attributes = array())
	{
		$this->attributes = array();
		if (!empty($attributes)) {
			if (!is_array($attributes) && !($attributes instanceof \Traversable)) {
				throw new \InvalidArgumentException(
					'The attributes is not Array and not Traversable.'
				);
			}
			foreach ($attributes as $name => $value) {
				$this->define($name, $value);
			}
		}
		return $this;
	}

	/**
	 * 属性名および初期値をセットします。
	 *
	 * @param string 属性名
	 * @param mixed 初期値
	 * @return $this
	 */
	public function define($name, $value = null)
	{
		if ($this->defined($name)) {
			throw new \InvalidArgumentException(
				sprintf('The attribute "%s" already exists.', $name)
			);
		}
		$this->attributes[$name] = $value;
		return $this;
	}

	/**
	 * 属性が定義されているかどうかを返します。
	 *
	 * @param string 属性名
	 * @return boolean 属性が定義されているかどうか
	 */
	public function defined($name)
	{
		return array_key_exists($name, $this->attributes);
	}

	/**
	 * 引数なしの場合は全ての属性を配列で返します。
	 * 引数ありの場合は全ての属性を引数の配列からセットして$thisを返します。
	 *
	 * @param array 属性の配列
	 * @return mixed 属性の配列 または $this
	 */
	public function attributes()
	{
		switch (func_num_args()) {
		case 0:
			return $this->attributes;
		case 1:
			$attributes = func_get_arg(0);
			if (!is_array($attributes) && !($attributes instanceof \Traversable)) {
				throw new \InvalidArgumentException(
					'The attributes is not Array and not Traversable.'
				);
			}
			foreach ($attributes as $name => $value) {
				$this->set($name, $value);
			}
			return $this;
		}
		throw new \InvalidArgumentException('Invalid argument count.');
	}

	/**
	 * 属性名を配列で返します。
	 *
	 * @return array 属性名の配列
	 */
	public function keys()
	{
		return array_keys($this->attributes);
	}

	/**
	 * 属性値を配列で返します。
	 *
	 * @return array 属性値の配列
	 */
	public function values()
	{
		return array_values($this->attributes);
	}

	/**
	 * 指定された属性の値をセットします。
	 *
	 * @param string 属性名
	 * @param mixed 属性値
	 */
	public function set($name, $value)
	{
		if (!$this->defined($name)) {
			throw new \InvalidArgumentException(
				sprintf('The attribute "%s" does not exists.', $name)
			);
		}
		$this->attributes[$name] = $value;
	}

	/**
	 * 指定された属性の値を返します。
	 *
	 * @param string 属性名
	 * @return mixed 属性値
	 */
	public function get($name)
	{
		if (!$this->defined($name)) {
			throw new \InvalidArgumentException(
				sprintf('The attribute "%s" does not exists.', $name)
			);
		}
		return $this->attributes[$name];
	}

	/**
	 * 指定された属性の値がセットされているかどうかを返します。
	 *
	 * @param string 属性名
	 * @return bool 値がセットされているかどうか
	 */
	public function has($name)
	{
		return isset($this->attributes[$name]);
	}

	/**
	 * magic setter
	 *
	 * @param string 属性名
	 * @param mixed 属性値
	 */
	public function __set($name, $value)
	{
		return $this->set($name, $value);
	}

	/**
	 * magic getter
	 *
	 * @param string 属性名
	 */
	public function __get($name)
	{
		return $this->get($name);
	}

	/**
	 * ArrayAccess::offsetExists()
	 *
	 * @param mixed
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return $this->has($offset);
	}

	/**
	 * ArrayAccess::offsetGet()
	 *
	 * @param mixed
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->get($offset);
	}

	/**
	 * ArrayAccess::offsetSet()
	 *
	 * @param mixed
	 * @param mixed
	 */
	public function offsetSet($offset, $value)
	{
		$this->set($offset, $value);
	}

	/**
	 * ArrayAccess::offsetUnset()
	 *
	 * @param mixed
	 */
	public function offsetUnset($offset)
	{
		if ($this->has($offset)) {
			unset($this->attributes[$offset]);
		}
	}

	/**
	 * IteratorAggregate::getIterator()
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->attributes);
	}

	/**
	 * Countable::count()
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->attributes);
	}

}
