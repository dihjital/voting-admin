<?php

namespace App\Traits;

use App\Events\UserChanged;
use Illuminate\Support\Str;

trait Uuid
{
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->setAttribute($model->getKeyName(), Str::uuid()->toString());
            }
        });

        static::created(function ($user) {
            event(new UserChanged($user));
        });

        static::updated(function ($user) {
            event(new UserChanged($user));
        });
    }

    public function getIncrementing()
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }
}