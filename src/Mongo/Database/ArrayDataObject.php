<?php

namespace App\Mongo\Database;

use Illuminate\Contracts\Support\Arrayable;
use ArrayObject as BaseArrayObject;

class ArrayDataObject extends BaseArrayObject implements Arrayable, \IteratorAggregate
{
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * Dynamically retrieve attributes on the document.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->get($name);
    }

    /**
     * Get a value from the document.
     *
     * @param mixed $key
     * @return mixed
     */
    public function get($key)
    {
        return data_get($this, $key);
    }

    /**
     * Get a Laravel collection for a specific key in the document.
     *
     * @param mixed $key
     * @return \Illuminate\Support\Collection
     */
    public function collect($key = null)
    {
        if ($key === null) {
            return collect($this->toArray());
        }

        return collect($this->get($key));
    }

    /**
     * Get document as array.
     *
     * @return array
     */
    public function toArray()
    {
        return (array) $this;
    }

    /**
     * Dump the document.
     * @return void
     */
    public function dump()
    {
        dump($this->toArray());
    }

    /**
     * Dump and die the document.
     * @return void
     */
    public function dd()
    {
        dd($this->toArray());
    }

    /**
     * Get a value from the document.
     *
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet(mixed $key): mixed
    {
        try {
            return parent::offsetGet($key);
        } catch (\ErrorException $e) {
            return null;
        }
    }
}
