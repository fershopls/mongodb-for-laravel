<?php

namespace App\Mongo\Database\Pipes;

use App\Mongo\Database\Collection;

class ReplaceIdSuffixOnFiltersCallsPipe
{
    protected array $commands = [
        'find',
        'findOne',
        'findOneAndDelete',
        'findOneAndUpdate',
        'findOneAndReplace',
        'updateOne',
        'updateMany',
        'deleteOne',
        'deleteMany',
        'replaceOne',
        // insert
        'insertOne',
        'insertMany',
        //
        'countDocuments',
    ];

    public function handle(CommandCalledData $command, \Closure $next)
    {
        if ($this->shouldProxy($command->name)) {
            if (
                isset($command->arguments[0])
                && is_array($command->arguments[0])
            ) {
                $command->arguments[0] = collect($command->arguments[0])
                    ->map(function ($value, $key) {
                        // every suffix _id to mongo_id
                        if (str($key)->endsWith('_id')) {
                            // if value is subclass of collection, call getKey method
                            if (is_subclass_of($value, Collection::class)) {
                                $value = $value->getKey();
                            }

                            // convert value to mongo_id
                            return mongo_id($value);
                        }

                        return $value;
                    })
                    ->toArray();
            }
        }

        return $next($command);
    }

    protected function shouldProxy(string $name)
    {
        return in_array($name, $this->commands);
    }
}
