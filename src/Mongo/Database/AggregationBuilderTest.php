<?php

use Mongo\Database\AggregationBuilder;
use Mongo\Database\Collection;

test('use aggregation builder with match stage', function () {
    $builder = new AggregationBuilder;

    $aggregation = $builder
        ->match([
            'status' => 'active'
        ])
        ->get();

    expect($aggregation)
        ->toBeArray()
        ->toEqual([
            [
                '$match' => [
                    'status' => 'active'
                ]
            ]
        ]);
});

test('use aggregation builder with multiple stages', function () {
    $builder = new AggregationBuilder;

    $aggregation = $builder
        ->match([
            'status' => 'active'
        ])
        ->sort([
            'created_at' => -1
        ])
        ->get();

    expect($aggregation)
        ->toBeArray()
        ->toEqual([
            [
                '$match' => [
                    'status' => 'active'
                ]
            ],
            [
                '$sort' => [
                    'created_at' => -1
                ]
            ]
        ]);
});

test('using Collection::aggregate', function () {
    $collection = new class extends Collection {
        public static string $collection = 'users';
    };

    $collection::insertMany([
        ['status' => 'active', 'created_at' => 1],
        ['status' => 'active', 'created_at' => 2],
        ['status' => 'inactive', 'created_at' => 3],
    ]);

    $aggregation = $collection::aggregate()
        ->match([
            'status' => 'active'
        ])
        ->sort([
            'created_at' => -1
        ])
        ->get();

    expect($aggregation)
        ->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->toHaveCount(2)
        ->each->toBeInstanceOf($collection::class)
        ->and($aggregation[0]->created_at)->toEqual(2)
        ->and($aggregation[1]->created_at)->toEqual(1);
});
