## Creating a Collection

```php
<?php

namespace App\Collections;

use App\Mongo\Database\Collection;

class ClientCollection extends Collection
{
}
```


## Calling Mongo DB Commands from the Collection

```php
use App\Collections\ClientCollection;

// Every call will be forwarded to
// the \MongoDB\Collection instance
// so we can call any method available

ClientCollection::find([]);

ClientCollection::findOne([]);

ClientCollection::aggregate($pipeline);

ClientCollection::insertOne($data);

ClientCollection::insertMany($data);

ClientCollection::updateOne($filter, $data);

ClientCollection::updateMany($filter, $data);

ClientCollection::deleteOne($filter);

ClientCollection::deleteMany($filter);
```

## Find one document

```php
// when calling aggregate or any find command
// on the collection instance, the documents will be
// automatically converted to the collection class

$client = ClientCollection::findOne([
    'title' => 'John Doe',
]);

// we can call collection methods
$client->markAsDisabled();

// read as array
$name = $client['name']; // John Doe

// read as object
$name = $client->name; // John Doe
// [!] only first level keys are accessible
$nested = $client->address->street->line1; // Attempt to read property "street" on array

// safe read
$line1 = $client->get('address.street.line1'); // 123 Main St
$undefined = $client->get('this.key.does.not.exists'); // null

// collect a field
$location = $client
    ->collect('address')
    ->only(['country', 'city'])
    ->join(', '); // USA, New York
```

## Find many documents

```php
$clients = ClientCollection::find([
    'status' => 'active',
]);

// $clients type is \Illuminate\Support\Collection
// so we can use all collection methods
```

## Route Binding

```php
Route::get('/clients/{client}', function (ClientCollection $client) {
    // if not found it will abort with 404
    return $client->collect()->only(['name', 'email']);
});

// by specified field
// example: /clients/user@gmail.com
Route::get('/clients/{client:email}', function (ClientCollection $client) {
    // if not found it will abort with 404
    return $client;
});
```

## Custom `findById`

```php
// Find or fail by id
$client = ClientCollection::findById($id);

// if $id is an instance of ObjectID it will be used as is
// if $id is string it will be automatically converted to ObjectID
```


## Custom `findOrFail`

```php
// Find or fail by id
// it will abort with 404 if not found
$client = ClientCollection::findOrFail($id);

// Find or fail by a filter
// it will abort with 404 if not found
$client = ClientCollection::findOrFail([
    'email' => 'user@gmail.com',
]);
```
