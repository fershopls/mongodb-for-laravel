<?php

namespace App\Mongo\Database\Pipes;

use App\Mongo\Database\Collection;

class WrapResultInLaravelCollectionPipe
{
    protected array $wrapManyCommands = [
        'find',
        'aggregate',
    ];

    protected array $wrapOneCommands = [
        'findOne',
        'findOneAndDelete',
        'findOneAndUpdate',
        'findOneAndReplace',
    ];

    public function handle(CommandCalledData $command, \Closure $next)
    {
        if ($this->shouldWrapMany($command->name)) {
            $command->result = collect($command->result)
                ->map(
                    fn($document) => tap(
                        value: new $command->collectionClass,
                        callback: fn(Collection $model) => $model->loadDocument($document))
                );
        } else if ($this->shouldWrapOne($command->name) && $command->result) {
            $command->result = tap(new $command->collectionClass, fn(Collection $model) => $model->loadDocument($command->result));
        }

        return $next($command);
    }

    protected function shouldWrapMany(string $name)
    {
        return in_array($name, $this->wrapManyCommands);
    }

    protected function shouldWrapOne(string $name)
    {
        return in_array($name, $this->wrapOneCommands);
    }
}
