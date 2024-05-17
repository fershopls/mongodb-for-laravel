<?php

namespace Mongo\Database;

use Illuminate\Support\Traits\Conditionable;

/**
 * @method $this lookup(array $lookup)
 * @method $this set(array $set)
 * @method $this unset(string|array $unset)
 * @method $this project(array $project)
 * @method $this match(array $match)
 * @method $this sort(array $sort)
 * @method $this limit(int $limit)
 * @method $this skip(int $skip)
 * @method $this group(array $group)
 * @method $this unwind(array $unwind)
 */
class AggregationBuilder
{
    use Conditionable;

    public function __construct(
        protected null|Collection $collection = null,
        protected array $aggregation = [],
    )
    {
    }

    public function get()
    {
        if ($this->collection) {
            return $this->collection::aggregate($this->aggregation);
        }

        return $this->aggregation;
    }

    public function __call(string $name, array $arguments)
    {
        $this->aggregation[] = [
            '$' . $name => $arguments[0] ?? []
        ];

        return $this;
    }

    public function dd()
    {
        dd($this->aggregation);
    }

    public function with(Relation $relation)
    {
        $this->lookup([
            'from' => $relation->from,
            'localField' => $relation->localField,
            'foreignField' => $relation->foreignField,
            'as' => $relation->as,
        ]);

        if ($relation->single) {
            $this->set([
                $relation->as => ['$arrayElemAt' => ['$' . $relation->as, 0]]
            ]);
        }

        return $this;
    }
}
