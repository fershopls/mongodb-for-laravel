<?php

namespace Mongo\Database;

use ArrayObject;
use ErrorException;
use Illuminate\Container\Container;
use Illuminate\Routing\ImplicitRouteBinding;
use Illuminate\Routing\Route;

test('accurately infers collection name from class name', function () {
    class ClientCollection extends Collection
    {
    }

    expect(ClientCollection::collectionName())->toBe('clients');

    class UserCollection extends Collection
    {
    }

    expect(UserCollection::collectionName())->toBe('users');

    class UserSettingsCollection extends Collection
    {
    }

    expect(UserSettingsCollection::collectionName())->toBe('user_settings');

    class DeeplyNestedNamedTestCollection extends Collection
    {
    }

    expect(DeeplyNestedNamedTestCollection::collectionName())->toBe('deeply_nested_named_tests');
});

test('allows to set the collection name', function () {
    $class = new class extends Collection {
        static string $collection = 'custom';
    };

    expect($class::collectionName())->toBe('custom');
});

test('call insertOne method statically', function () {
    $class = new class extends Collection {
        static string $collection = 'custom';
    };

    $class::insertOne(['id' => 1, 'name' => 'John']);

    expect($class::findOne(['id' => 1])->name)->toBe('John');
});

test('call insertMany method statically', function () {
    $class = new class extends Collection {
        static string $collection = 'custom';
    };

    $class::insertMany([
        ['id' => 1, 'name' => 'John'],
        ['id' => 2, 'name' => 'Jane'],
    ]);

    expect($class::findOne(['id' => 1])->name)->toBe('John');
    expect($class::findOne(['id' => 2])->name)->toBe('Jane');
});

test('call updateOne method statically', function () {
    $class = new class extends Collection {
        static string $collection = 'custom';
    };

    $class::insertOne(['id' => 1, 'name' => 'John']);

    expect($class::findOne(['id' => 1])->name)->toBe('John');

    $class::updateOne(['id' => 1], ['$set' => ['name' => 'Jane']]);

    expect($class::findOne(['id' => 1])->name)->toBe('Jane');
});

test('call updateMany method statically', function () {
    $class = new class extends Collection {
        static string $collection = 'custom';
    };

    $class::insertMany([
        ['id' => 1, 'name' => 'John'],
        ['id' => 2, 'name' => 'John'],
    ]);

    expect($class::find(['name' => 'John'])->count())->toBe(2);

    $class::updateMany(['name' => 'John'], ['$set' => ['name' => 'Jane']]);

    expect($class::find(['name' => 'Jane'])->count())->toBe(2);
});

test('call deleteOne method statically', function () {
    $class = new class extends Collection {
        static string $collection = 'custom';
    };

    $class::insertOne(['id' => 1, 'name' => 'John']);

    expect($class::findOne(['id' => 1])->name)->toBe('John');

    $class::deleteOne(['id' => 1]);

    expect($class::findOne(['id' => 1]))->toBeNull();
});

test('call deleteMany method statically', function () {
    $class = new class extends Collection {
        static string $collection = 'custom';
    };

    $class::insertMany([
        ['id' => 1, 'name' => 'John'],
        ['id' => 2, 'name' => 'John'],
    ]);

    expect($class::find(['name' => 'John'])->count())->toBe(2);

    $class::deleteMany(['name' => 'John']);

    expect($class::find(['name' => 'John'])->count())->toBe(0);
});

test('findOne proxy method', function () {
    $class = new class extends Collection {
        static string $collection = 'custom';
    };

    $class::insertOne(['id' => 1, 'name' => 'John']);

    $model = $class::findOne(['id' => 1]);

    expect($model)
        ->toBeInstanceOf($class::class)
        ->toBeInstanceOf(ArrayObject::class);
});

test('findOne method returns null if no document is found', function () {
    $class = new class extends Collection {
        static string $collection = 'custom';
    };

    expect($class::findOne(['id' => 1]))->toBeNull();
});

test('find method returns a collection of models', function () {
    $class = new class extends Collection {
        static string $collection = 'custom';
    };

    $class::insertMany([
        ['id' => 1, 'name' => 'John'],
        ['id' => 2, 'name' => 'Jane'],
    ]);

    $models = $class::find();

    expect($models)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($models->count())->toBe(2)
        ->and($models[0])->toBeInstanceOf($class::class)
        ->and($models[1])->toBeInstanceOf($class::class);
});

test('aggregate method returns a collection of models', function () {
    $class = new class extends Collection {
        static string $collection = 'custom';
    };

    $class::insertMany([
        ['id' => 1, 'name' => 'John'],
        ['id' => 2, 'name' => 'Jane'],
    ]);

    $models = $class::aggregate([['$match' => ['id' => 1]]]);

    expect($models)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($models->count())->toBe(1)
        ->and($models[0])->toBeInstanceOf($class::class);
});

test('findOneAndDelete proxy', function () {
    $class = new class extends Collection {
        static string $collection = 'custom';
    };

    $class::insertOne(['id' => 1, 'name' => 'John']);

    $model = $class::findOneAndDelete(['id' => 1]);

    expect($model)
        ->toBeInstanceOf($class::class)
        ->toBeInstanceOf(ArrayObject::class)
        ->and($class::findOne(['id' => 1]))->toBeNull();
});

test('findOneAndUpdate proxy', function () {
    $class = new class extends Collection {
        static string $collection = 'custom';
    };

    $class::insertOne(['id' => 1, 'name' => 'John']);

    $model = $class::findOneAndUpdate(['id' => 1], ['$set' => ['name' => 'Jane']]);

    expect($model)
        ->toBeInstanceOf($class::class)
        ->toBeInstanceOf(ArrayObject::class)
        ->and($class::findOne(['id' => 1])->name)->toBe('Jane');
});

test('findOneAndReplace proxy', function () {
    $class = new class extends Collection {
        static string $collection = 'custom';
    };

    $class::insertOne(['id' => 1, 'name' => 'John']);

    $model = $class::findOneAndReplace(['id' => 1], ['id' => 1, 'name' => 'Jane']);

    expect($model)
        ->toBeInstanceOf($class::class)
        ->toBeInstanceOf(ArrayObject::class)
        ->and($class::findOne(['id' => 1])->name)->toBe('Jane');
});

test('find one document', function () {
    $clientCollection = new class extends Collection {
        static string $collection = 'clients';

        public function getLocation()
        {
            return "{$this->get('address.country')}, {$this->get('address.city')}";
        }
    };

    $clientCollection::insertOne([
        'firstname' => 'John',
        'lastname' => 'Doe',
        'email' => 'john@doe.com',
        'address' => [
            'country' => 'USA',
            'city' => 'Springfield',
            'street' => [
                'line1' => '742 Evergreen Terrace',
                'line2' => 'Apt. 2'
            ],
        ],
    ]);

    $client = $clientCollection::findOne();

    expect($client)
        ->toBeInstanceOf($clientCollection::class)
        // _id cast string
        ->and($client->_id)->toBeString()
        // method getKey
        ->and($client->getKey())->toBe($client['_id'])
        // array access
        ->and($client['lastname'])->toBe('Doe')
        ->and($client['non_existent_key'])->toBeNull()
        // property access
        ->and($client->firstname)->toBe('John')
        ->and($client->non_existent_key)->toBeNull()
        ->and(fn() => $client->address->street->line1)->toThrow(ErrorException::class, 'Attempt to read property "street" on array')
        // safe get
        ->and($client->get('non_existent_key'))->toBeNull()
        ->and($client->get('email'))->toBe('john@doe.com')
        // safe get nested
        ->and($client->get('address.street.non_existent_key'))->toBeNull()
        ->and($client->get('address.street.line1'))->toBe('742 Evergreen Terrace')
        // collect all
        ->and($client->collect()->only('firstname', 'lastname')->join(' '))->toBe('John Doe')
        // collect property
        ->and($client->collect('address'))->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($client->collect('address')->only('country', 'city')->join(', '))->toBe('USA, Springfield')
        // collect nested property
        ->and($client->collect('address.street')->only('line1', 'line2')->join(', '))->toBe('742 Evergreen Terrace, Apt. 2')
        // call method
        ->and($client->getLocation())->toBe('USA, Springfield');
});

test('authorize property', function () {
    $clientCollection = new class extends Collection {
        static string $collection = 'clients';
    };

    $clientCollection::insertMany([
        ['firstname' => 'John', 'is_admin' => true],
        ['firstname' => 'Jane', 'is_admin' => false],
    ]);

    $clients = $clientCollection::find();

    expect(fn() => $clients[0]->authorize('is_admin', true))->not->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    expect(fn() => $clients[1]->authorize('is_admin', true))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('authorize nested property', function () {
    $clientCollection = new class extends Collection {
        static string $collection = 'clients';
    };

    $clientCollection::insertMany([
        ['firstname' => 'John', 'meta' => ['admin' => ['is_admin' => true]]],
        ['firstname' => 'Jane', 'meta' => ['admin' => ['is_admin' => false]]],
    ]);

    $clients = $clientCollection::find();

    expect(fn() => $clients[0]->authorize('meta.admin.is_admin', true))->not->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    expect(fn() => $clients[1]->authorize('meta.admin.is_admin', true))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('authorize callback', function () {
    $clientCollection = new class extends Collection {
        static string $collection = 'clients';
    };

    $clientCollection::insertMany([
        ['firstname' => 'John', 'roles' => ['admin']],
        ['firstname' => 'Jane', 'roles' => ['user']],
    ]);

    $clients = $clientCollection::find();

    expect(fn() => $clients[0]->authorize('roles', fn($roles) => in_array('admin', $roles)))->not->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    expect(fn() => $clients[1]->authorize('roles', fn($roles) => in_array('admin', $roles)))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('route binding', function () {
    class RouteBindingClientCollection extends Collection
    {
        static string $collection = 'clients';
    }

    ;

    $insertResult = RouteBindingClientCollection::insertOne([
        'name' => 'John Doe',
    ]);

    $insertedId = $insertResult->getInsertedId();

    $action = ['uses' => fn(RouteBindingClientCollection $client) => $client];

    $route = new Route('GET', '/test', $action);
    $route->parameters = ['client' => (string)$insertedId];
    $route->prepareForSerialization();

    $container = Container::getInstance();
    ImplicitRouteBinding::resolveForRoute($container, $route);

    $client = $route->parameter('client');
    expect($client)->not->toBeNull();
    expect($client->getKey())->toBe((string)$insertedId);
    expect($client->name)->toBe('John Doe');
});

test('route binding custom field', function () {
    class RouteBindingCustomFieldClientCollection extends Collection
    {
        static string $collection = 'clients';
    }

    ;

    RouteBindingCustomFieldClientCollection::insertOne([
        'name' => 'John Doe',
        'username' => 'john_doe',
    ]);

    $action = ['uses' => fn(RouteBindingCustomFieldClientCollection $client) => $client];

    $route = new Route('GET', '/test/{client:username}', $action);
    $route->parameters = ['client' => 'john_doe'];
    $route->prepareForSerialization();

    $container = Container::getInstance();
    ImplicitRouteBinding::resolveForRoute($container, $route);

    $client = $route->parameter('client');
    expect($client)->not->toBeNull();
    expect($client->name)->toBe('John Doe');
});

test('findById', function () {
    class FindByIdClientCollection extends Collection
    {
        static string $collection = 'clients';
    }

    ;

    $insertResult = FindByIdClientCollection::insertOne([
        'name' => 'John Doe',
    ]);

    $insertedId = $insertResult->getInsertedId();

    $client = FindByIdClientCollection::findById($insertedId);

    expect($client)->not->toBeNull()
        ->and($client->getKey())->toBe((string)$insertedId)
        ->and($client->name)->toBe('John Doe');

    $client = FindByIdClientCollection::findById('non-existent-id');
    expect($client)->toBeNull();
});

test('findOrFail by id', function () {
    class FindOrFailClientCollection extends Collection
    {
        static string $collection = 'clients';
    }

    ;

    $insertResult = FindOrFailClientCollection::insertOne([
        'name' => 'John Doe',
    ]);

    $insertedId = $insertResult->getInsertedId();

    $client = FindOrFailClientCollection::findOrFail($insertedId);

    expect($client)->not->toBeNull()
        ->and($client->getKey())->toBe((string)$insertedId)
        ->and($client->name)->toBe('John Doe');

    expect(fn() => FindOrFailClientCollection::findOrFail('non-existent-id'))->toThrow(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
});

test('findOrFail by filter', function () {
    class FindOrFailByFilterClientCollection extends Collection
    {
        static string $collection = 'clients';
    }

    ;

    $insertResult = FindOrFailByFilterClientCollection::insertOne([
        'name' => 'John Doe',
    ]);

    $client = FindOrFailByFilterClientCollection::findOrFail(['name' => 'John Doe']);

    expect($client)->not->toBeNull()
        ->and($client->name)->toBe('John Doe');

    expect(fn() => FindOrFailByFilterClientCollection::findOrFail(['name' => 'Jane Doe']))->toThrow(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
});

test('allows to set the primary key', function () {
    class AllowsToSetThePrimaryKeyCollection extends Collection
    {
        static string $primaryKey = 'custom_id';
    }

    ;

    expect(AllowsToSetThePrimaryKeyCollection::$primaryKey)->toBe('custom_id');

    AllowsToSetThePrimaryKeyCollection::insertOne([
        'custom_id' => 1,
        'name' => 'John Doe',
    ]);

    expect(AllowsToSetThePrimaryKeyCollection::findById(1))->not->toBeNull();
    expect(AllowsToSetThePrimaryKeyCollection::findOrFail(1))->not->toBeNull();

    $action = ['uses' => function (AllowsToSetThePrimaryKeyCollection $model) {
        return $model;
    }];

    $route = new Route('GET', '/test', $action);
    $route->parameters = ['model' => 1];
    $route->prepareForSerialization();

    $container = Container::getInstance();
    ImplicitRouteBinding::resolveForRoute($container, $route);

    $parameter = $route->parameter('model');
    expect($parameter)->not->toBeNull();
    expect($parameter->getKey())->toBe(1);
    expect($parameter->name)->toBe('John Doe');
});

test('allows to set the hidden attributes', function () {
    $class = new class extends Collection {
        public static string $collection = 'custom';
        public array $hidden = ['password', 'secret'];
    };

    $class::insertOne([
        'name' => 'John Doe',
        'username' => 'john_doe',
        'password' => 'password',
        'secret' => 'secret',
    ]);

    $model = $class::findOne();
    expect($model)->not->toBeNull();

    $model = $model->toArray();
    expect($model)->not->toHaveKeys(['password', 'secret'])
        ->and($model)->toHaveKeys(['_id', 'name', 'username']);
});

test('allows to disable automatic serialization', function () {
    $class = new class extends Collection {
        public static string $collection = 'custom';
        public static bool $automaticMongoSerialize = false;
    };

    $class::insertMany([
        ['name' => 'John Doe', 'array' => [1, 2, 3]],
        ['name' => 'Jane Doe', 'array' => [4, 5, 6]],
    ]);

    $model = $class::findOne();
    expect($model)->not->toBeInstanceOf($class::class)
        ->and($model->_id)->toBeInstanceOf(\MongoDB\BSON\ObjectId::class)
        ->and($model->name)->toBe('John Doe')
        ->and($model->array)->toBeInstanceOf(\MongoDB\Model\BSONArray::class);


    $models = $class::find();
    expect($models)->toBeInstanceOf(\MongoDB\Driver\Cursor::class);

    $models = iterator_to_array($models);
    expect($models[0])->not->toBeInstanceOf($class::class)
        ->and($models[1])->not->toBeInstanceOf($class::class)
        ->and($models[1]->_id)->toBeInstanceOf(\MongoDB\BSON\ObjectId::class)
        ->and($models[1]->array)->toBeInstanceOf(\MongoDB\Model\BSONArray::class);
});

test('update method', function () {
    $class = new class extends Collection {
        public static string $collection = 'custom';
    };

    $result = $class::insertMany([
        [
            'name' => 'Timmy',
            'age' => 10,
        ],
        [
            'name' => 'John Doe',
            'age' => 30,
        ],
        [
            'name' => 'John Doe',
            'age' => 30,
        ],
        [
            'name' => 'Jane Doe',
            'age' => 20,
        ],
    ]);

    $insertedIds = $result->getInsertedIds();
    $timmyId = $insertedIds[0];
    $johnId = $insertedIds[1];
    $janeId = $insertedIds[3];

    $john = $class::findOrFail($johnId);
    $john->update([
        '$set' => ['age' => 31],
    ]);

    expect($john->age)->toBe(30);
    $john->refresh();
    expect($john->age)->toBe(31);

    $john = $class::findOrFail($johnId);
    expect($john->age)->toBe(31);

    $jane = $class::findById($janeId);
    expect($jane->age)->toBe(20);

    $timmy = $class::findById($timmyId);
    expect($timmy->age)->toBe(10);
});

test('updateSet method');

test('delete method', function () {
    $class = new class extends Collection {
        public static string $collection = 'custom';
    };

    $insertResult = $class::insertMany([
        [
            'username' => 'timmy',
            'name' => 'Timmy',
        ],
        [
            'username' => 'john_doe',
            'name' => 'John Doe',
        ],
        [
            'username' => 'john_doe',
            'name' => 'John Doe',
        ],
        [
            'username' => 'jane_doe',
            'name' => 'Jane Doe',
        ],
    ]);

    $insertedIds = $insertResult->getInsertedIds();

    $timmyId = $insertedIds[0];
    $johnId = $insertedIds[1];
    $janeId = $insertedIds[3];

    $john = $class::findOrFail($johnId);
    $john->delete();

    expect($class::findById($johnId))->toBeNull();

    $jane = $class::findOrFail($janeId);
    expect($jane)->not->toBeNull();

    $timmy = $class::findById($timmyId);
    expect($timmy)->not->toBeNull();

    expect($class::countDocuments())->toBe(3);
});

test('automatic string to object id casting on filter for _id suffix', function () {
    $class = new class extends Collection {
        public static string $collection = 'custom';
    };

    $id = mongo_id();
    $class::insertOne([
        'name' => 'John Doe',
        'user_id' => $id,
    ]);

    $model = $class::findOne(['user_id' => (string)$id]);

    expect($model)->toMatchArray([
        'name' => 'John Doe',
        'user_id' => (string)$id,
    ]);
});

test('automatic collection to object id casting on filter for _id suffix', function () {
    $userCollection = new class extends Collection {
        public static string $collection = 'users';
    };

    $todoCollection = new class extends Collection {
        public static string $collection = 'todos';
    };

    $userCollection::insertOne([
        'name' => 'John Doe',
    ]);

    $user = $userCollection::findOne();

    $todoCollection::insertOne([
        'title' => 'Buy milk',
        'user_id' => $user,
    ]);

    $todo = $todoCollection::findOne(['user_id' => $user]);

    expect($todo)
        ->not->toBeNull()
        ->toMatchArray([
            'title' => 'Buy milk',
            'user_id' => (string) $user->_id,
        ]);
});

