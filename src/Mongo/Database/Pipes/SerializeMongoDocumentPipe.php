<?php

namespace Mongo\Database\Pipes;

class SerializeMongoDocumentPipe
{
    public function handle($result, \Closure $next)
    {
        return $next(mongo_serialize($result));
    }
}
