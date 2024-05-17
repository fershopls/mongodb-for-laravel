<?php

namespace App\Mongo;

class RefreshMongoDatabase
{
    public function __invoke()
    {
        $collections = mongo()->listCollections();

        foreach ($collections as $collection) {
            mongo()
                ->selectCollection($collection->getName())
                ->drop();
        }
    }
}
