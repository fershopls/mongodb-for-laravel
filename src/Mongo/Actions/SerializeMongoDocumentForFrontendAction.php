<?php

namespace App\Mongo\Actions;

class SerializeMongoDocumentForFrontendAction
{
    protected array $casts;

    protected function getCasts()
    {
        return [
            \MongoDB\BSON\UTCDateTime::class => fn ($value) => \Illuminate\Support\Carbon::parse($value->toDateTime()),
            \MongoDB\BSON\ObjectId::class => fn  ($value) => (string) $value,
            \MongoDB\Model\BSONDocument::class => fn ($value) => $this->execute((array) $value),
            \MongoDB\Model\BSONArray::class => fn ($value) => $this->execute((array) $value),
        ];
    }

    public function execute($document)
    {
        if (!is_iterable($document)) {
            return $this->castValue($document);
        }

        return collect(iterator_to_array($document))
            ->map(fn ($value) => $this->execute($value))
            ->toArray();
    }

    protected function castValue($value)
    {
        $casts = $this->casts ?? $this->casts = $this->getCasts();

        foreach ($casts as $class => $cast) {
            if ($value instanceof $class) {
                return $cast($value);
            }
        }

        return $value;
    }
}
