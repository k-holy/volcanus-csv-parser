<?php
/**
 * Volcanus libraries for PHP 8.1~
 *
 * @copyright k-holy <k.holy74@gmail.com>
 * @license The MIT License (MIT)
 */

namespace Volcanus\CsvParser;

/**
 * 設定クラス
 *
 * @author k.holy74@gmail.com
 */
class Configuration implements \ArrayAccess, \IteratorAggregate, \Countable
{

    /**
     * @var array 属性の配列
     */
    private array $attributes;

    /**
     * コンストラクタ
     *
     * @param iterable $attributes 属性の配列
     */
    public function __construct(iterable $attributes = [])
    {
        $this->attributes = [];
        if (!empty($attributes)) {
            foreach ($attributes as $name => $value) {
                $this->define($name, $value);
            }
        }
    }

    /**
     * 属性名および初期値をセットします。
     *
     * @param string $name 属性名
     * @param mixed $value 初期値
     * @return self
     */
    public function define(string $name, mixed $value = null): self
    {
        if (array_key_exists($name, $this->attributes)) {
            throw new \InvalidArgumentException(
                sprintf('The attribute "%s" already exists.', $name));
        }
        if (method_exists($this, $name)) {
            throw new \InvalidArgumentException(
                sprintf('The attribute "%s" is already defined as a method.', $name)
            );
        }
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * ArrayAccess::offsetSet()
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!array_key_exists($offset, $this->attributes)) {
            throw new \InvalidArgumentException(
                sprintf('The attribute "%s" does not exists.', $offset));
        }
        $this->attributes[$offset] = $value;
    }

    /**
     * ArrayAccess::offsetGet()
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (!array_key_exists($offset, $this->attributes)) {
            throw new \InvalidArgumentException(
                sprintf('The attribute "%s" does not exists.', $offset));
        }
        return $this->attributes[$offset];
    }

    /**
     * ArrayAccess::offsetUnset()
     *
     * @param mixed $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        if (array_key_exists($offset, $this->attributes)) {
            $this->attributes[$offset] = null;
        }
    }

    /**
     * ArrayAccess::offsetExists()
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * magic setter
     *
     * @param string $name 属性名
     * @param mixed $value 属性値
     */
    public function __set(string $name, mixed $value)
    {
        $this->offsetSet($name, $value);
    }

    /**
     * magic getter
     *
     * @param string $name 属性名
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->offsetGet($name);
    }

    /**
     * magic call method
     *
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call(string $name, array $args)
    {
        if (array_key_exists($name, $this->attributes)) {
            $value = $this->attributes[$name];
            if (is_callable($value)) {
                return call_user_func_array($value, $args);
            }
        }
        throw new \BadMethodCallException(
            sprintf('Undefined Method "%s" called.', $name)
        );
    }

    /**
     * __toString
     */
    public function __toString()
    {
        return (string)var_export(iterator_to_array($this->getIterator()), true);
    }

    /**
     * IteratorAggregate::getIterator()
     *
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->attributes);
    }

    /**
     * Countable::count()
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->attributes);
    }

}
