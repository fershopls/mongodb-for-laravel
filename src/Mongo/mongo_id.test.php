<?php

test('mongo_id with null returns new object id', function () {
    $id = mongo_id();
    expect($id)->toBeInstanceOf(MongoDB\BSON\ObjectId::class);
});

test('mongo_id with valid string returns object id', function () {
    $id = mongo_id('5f5f3b3b7f7f7f7f7f7f7f7f');

    expect($id)
        ->toBeInstanceOf(MongoDB\BSON\ObjectId::class)
        ->and((string) $id)->toBe('5f5f3b3b7f7f7f7f7f7f7f7f');
});

test('mongo_id with invalid string returns string', function () {
    $id = mongo_id('invalid-id');

    expect($id)->toBe('invalid-id');
});

test('mongo_id with object id returns object id', function () {
    $id = new MongoDB\BSON\ObjectId('5f5f3b3b7f7f7f7f7f7f7f7f');
    $result = mongo_id($id);

    expect($result)
        ->toBeInstanceOf(MongoDB\BSON\ObjectId::class)
        ->toBe($id)
        ->and((string) $result)->toBe('5f5f3b3b7f7f7f7f7f7f7f7f');
});

test('mongo_id with array of ids returns array of object ids', function () {
    $ids = [
        '5f5f3b3b7f7f7f7f7f7f7f7f',
        '5f5f3b3b7f7f7f7f7f7f7f8f',
    ];
    $result = mongo_id($ids);

    expect($result)->toBeArray()->toHaveLength(2)
        ->and($result[0])->toBeInstanceOf(MongoDB\BSON\ObjectId::class)
        ->and($result[1])->toBeInstanceOf(MongoDB\BSON\ObjectId::class)
        ->and((string)$result[0])->toBe('5f5f3b3b7f7f7f7f7f7f7f7f')
        ->and((string)$result[1])->toBe('5f5f3b3b7f7f7f7f7f7f7f8f');
});

test('mongo_id with mixed array returns mixed array', function () {
    $ids = [
        '5f5f3b3b7f7f7f7f7f7f7f7f',
        'invalid-id',
        new MongoDB\BSON\ObjectId('5f5f3b3b7f7f7f7f7f7f7f8f'),
    ];
    $result = mongo_id($ids);

    expect($result)->toBeArray()->toHaveLength(3)
        ->and($result[0])->toBeInstanceOf(MongoDB\BSON\ObjectId::class)
        ->and($result[1])->toBe('invalid-id')
        ->and($result[2])->toBeInstanceOf(MongoDB\BSON\ObjectId::class)
        ->and((string)$result[0])->toBe('5f5f3b3b7f7f7f7f7f7f7f7f')
        ->and((string)$result[2])->toBe('5f5f3b3b7f7f7f7f7f7f7f8f');
});

test('mongo_id int id returns int id', function () {
    $id = 123;
    $result = mongo_id($id);

    expect($result)
        ->toBeNumeric()
        ->toBe($id);
});
