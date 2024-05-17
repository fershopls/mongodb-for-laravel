<?php

namespace Mongo\Database\Pipes;

class CommandCalledData
{
    public function __construct(
        public string $collectionClass,
        public string $name,
        public array $arguments = [],
        public mixed $result = null,
    )
    {
    }
}
