<?php

namespace Mongo\Database;

use Mongo\Database\Pipes\CommandCalledData;
use Mongo\Database\Pipes\ReplaceIdSuffixOnFiltersCallsPipe;
use Mongo\Database\Pipes\SerializeMongoDocumentPipe;
use Mongo\Database\Pipes\WrapResultInLaravelCollectionPipe;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Pipeline\Pipeline;

/**
 * @method static \Illuminate\Support\Collection find(array $filter = [], array $options = [])
 * @method static static findOne(array $filter = [], array $options = [])
 * @method static static findOneAndDelete(array $filter, array $options = [])
 * @method static static findOneAndReplace(array $filter, array|object $replacement, array $options = [])
 * @method static static findOneAndUpdate(array $filter, array $update, array $options = [])
 * @method static \MongoDB\DeleteResult deleteOne(array $filter, array $options = [])
 * @method static \MongoDB\DeleteResult deleteMany(array $filter, array $options = [])
 * @method static \MongoDB\InsertOneResult insertOne(array|object $document, array $options = [])
 * @method static \MongoDB\InsertManyResult insertMany(array $documents, array $options = [])
 * @method static \MongoDB\UpdateResult updateOne(array $filter, array $update, array $options = [])
 * @method static \MongoDB\UpdateResult updateMany(array $filter, array $update, array $options = [])
 * @method static \MongoDB\UpdateResult replaceOne(array $filter, array|object $replacement, array $options = [])
 * @method static \MongoDB\BulkWriteResult bulkWrite(array $operations, array $options = [])
 * @method static integer countDocuments(array $filter = [], array $options = [])
 */
class Collection extends ArrayDataObject implements UrlRoutable
{
    /**
     * The name of the collection.
     * If not set, it will be automatically inferred from the class name.
     *
     * @var string
     */
    static string $collection;

    /**
     * The primary key field for the collection.
     *
     * @var string
     */
    static string $primaryKey = '_id';

    /**
     * Whether to automatically serialize as array the result of the query.
     *
     * @var bool
     */
    static bool $automaticMongoSerialize = true;

    /**
     * The attributes that should be hidden when calling toArray.
     *
     * @var array
     */
    public array $hidden = [];

    /**
     * Get the collection instance.
     *
     * @return \MongoDB\Collection
     */
    public static function collection(): \MongoDB\Collection
    {
        $collectionName = static::collectionName();
        $bindName = 'mongo_collection_' . $collectionName;

        if (!app()->bound($bindName)) {
            app()->bind($bindName, fn() => mongo()->selectCollection($collectionName));
        }

        return app($bindName);
    }

    /**
     * Get the collection name.
     *
     * @return string
     */
    public static function collectionName(): string
    {
        return static::$collection ?? str(static::class)
            ->classBasename()
            ->replaceEnd('Collection', '')
            ->snake()
            ->plural()
            ->toString();
    }

    /**
     * Convert nested document to array.
     *
     * @param mixed $document
     * @return mixed
     */
    public static function mongoSerialize($document)
    {
        return mongo_serialize($document);
    }

    /**
     * Dynamically pass method calls to the collection instance.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        /** @var CommandCalledData $command */
        $command = app(Pipeline::class)
            ->send(new CommandCalledData(
                collectionClass: static::class,
                name: $name,
                arguments: $arguments,
            ))
            ->through([
                ReplaceIdSuffixOnFiltersCallsPipe::class,
            ])
            ->thenReturn();

        $command->result = static::collection()->{$command->name}(...$command->arguments);

        if (!static::$automaticMongoSerialize) {
            return $command->result;
        }

        return app(Pipeline::class)
            ->send($command)
            ->through(array_filter([
                SerializeMongoDocumentPipe::class,
                WrapResultInLaravelCollectionPipe::class
            ]))
            ->then(fn(CommandCalledData $command) => $command->result);
    }

    /**
     * Aggregate the collection.
     * If no pipeline is provided, it will return a new AggregationBuilder instance.
     *
     * @param $pipeline
     * @return AggregationBuilder|mixed
     */
    public static function aggregate($pipeline = null)
    {
        if (is_null($pipeline)) {
            return new AggregationBuilder(new static, []);
        }

        return static::__callStatic('aggregate', [$pipeline]);
    }

    /**
     * Find a document by its primary key.
     *
     * @param mixed $id
     * @return mixed|null
     */
    public static function findById($id)
    {
        return static::findOne([static::$primaryKey => mongo_id($id)]);
    }

    /**
     * Find a single document matching the filter or throw a 404 exception.
     *
     * @param mixed $filter
     * @return mixed
     */
    public static function findOrFail(mixed $filter)
    {
        if (!is_array($filter)) {
            return static::findOrFail([static::$primaryKey => mongo_id($filter)]);
        }

        $document = static::findOne($filter);

        abort_if(!$document, 404);

        return $document;
    }

    /**
     * Get document as array.
     *
     * @return array
     */
    public function toArray()
    {
        return collect((array)$this)
            ->except($this->hidden)
            ->toArray();
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return static::$primaryKey;
    }


    /**
     * Retrieve the model for a bound value.
     *
     * @param mixed $value The value to bind.
     * @param string|null $field The field to bind to.
     * @return self
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $key = $field ?? static::$primaryKey;

        $document = static::collection()->findOne(
            [$key => mongo_id($value)]
        );

        abort_if(!$document, 404);

        $this->loadDocument($document);

        return $this;
    }

    public function loadDocument($document)
    {
        $document = static::mongoSerialize($document);
        $this->exchangeArray($document);
    }

    public function getRouteKey()
    {
        // dd('getRouteKey');
    }

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        // dd('resolveChildRouteBinding', $childType, $value, $field);
    }

    /**
     * Get the primary key value.
     * @return mixed
     */
    public function getKey()
    {
        return $this->get($this->getKeyName());
    }

    /**
     * Get the primary key name.
     *
     * @return string
     */
    public function getKeyName()
    {
        return static::$primaryKey;
    }

    /**
     * Update the document.
     *
     * @param array $update
     * @param array $options
     * @return \MongoDB\UpdateResult
     */
    public function update(array $update, array $options = [])
    {
        return static::collection()->updateOne(
            [static::$primaryKey => mongo_id($this->getKey())],
            $update,
            $options,
        );
    }

    /**
     * Delete the document.
     *
     * @param array $options
     * @return \MongoDB\DeleteResult
     */
    public function delete(array $options = [])
    {
        return static::collection()->deleteOne(
            [static::$primaryKey => mongo_id($this->getKey())],
            $options,
        );
    }

    /**
     * Refresh the document.
     * @return static
     */
    public function refresh()
    {
        $document = static::collection()->findOne(
            [static::$primaryKey => mongo_id($this->getKey())]
        );

        $document = static::mongoSerialize($document);

        $this->loadDocument($document);

        return $this;
    }

    /**
     * Authorize the document for a specific field.
     *
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public function authorize($field, $value)
    {
        if (is_callable($value)) {
            $condition = value($value, $this->get($field));
        } else {
            $condition = $this->get($field) === $value;
        }
        abort_unless($condition, 403);
    }
}