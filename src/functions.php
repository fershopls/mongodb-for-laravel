<?php

if (!function_exists('mongo')) {
    function mongo(): MongoDB\Database
    {
        /** @var string $mongoUri */
        $mongoUri = config('app.mongo_uri', 'mongodb://localhost:27017');

        $mongo = new MongoDB\Client($mongoUri);
        $database = app()->runningUnitTests() ? config('app.mongo_db_test', 'test') : config('app.mongo_db');

        if (!$database) {
            throw new Exception('MONGO_DB is not set in .env');
        }

        return $mongo->$database;
    }
}

if (!function_exists('mongo_id')) {
    /**
     * @param string|null $id
     * @return mixed
     */
    function mongo_id($id = null)
    {
        if ($id === null) {
            return new MongoDB\BSON\ObjectId;
        } else if (is_string($id)) {
            try {
                return new MongoDB\BSON\ObjectId($id);
            } catch (Exception $e) {
                return $id;
            }
        } else if ($id instanceof MongoDB\BSON\ObjectId) {
            return $id;
        } else if (is_array($id)) {
            return array_map('mongo_id', $id);
        }

        return $id;
    }
}


if (!function_exists('mongo_date')) {
    /**
     * @param int $milliseconds
     * @return \MongoDB\BSON\UTCDateTime
     */
    function mongo_date($date = null): MongoDB\BSON\UTCDateTime
    {
        if ($date instanceof \Illuminate\Support\Carbon) {
            $date = $date->getTimestampMs();
        }

        return new MongoDB\BSON\UTCDateTime($date);
    }
}

if (!function_exists('mongo_serialize')) {
    /**
     * @param $document
     * @return array
     */
    function mongo_serialize($document)
    {
        /** @var \App\Mongo\Actions\SerializeMongoDocumentForFrontendAction $serializer */
        $serializer = app(\App\Mongo\Actions\SerializeMongoDocumentForFrontendAction::class);
        return $serializer->execute($document);
    }
}
