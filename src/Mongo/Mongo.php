<?php

namespace App\Mongo;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class Mongo
{
    public static function install()
    {
        static::useMongoUserProvider();
        static::discoverPolicyNames();
    }

    public static function useMongoUserProvider()
    {
        Auth::provider('mongo', fn() => new \App\Mongo\Auth\MongoUserProvider);
    }

    public static function discoverPolicyNames()
    {
        Gate::guessPolicyNamesUsing(function (string $modelClass) {
            if (str_starts_with($modelClass, 'App\\Collections\\')) {
                return 'App\\Policies\\' . str($modelClass)
                        ->classBasename()
                        ->replaceEnd('Collection', '')
                        ->append('Policy');
            } else if (str_starts_with($modelClass, 'App\\Models\\')) {
                return 'App\\Policies\\' . class_basename($modelClass) . 'Policy';
            }
        });
    }
}
