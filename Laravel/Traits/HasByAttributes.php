<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasByAttributes
{

    /**
     * Set event hooks
     *
     * @return void
     */
    protected static function bootHasByAttributes()
    {
        static::created(function (Model $model) {
            // Model after creation has not loaded created_by attribute
            $model->updateByAttribute('created_by', null, true)->save();
        });

        static::updating(function (Model $model) {
            $model->updateByAttribute('updated_by');
        });

        static::deleted(function (Model $model) {
            $model->updateByAttribute('deleted_by')->save();
        });

        if(method_exists(static::class, 'restoring')) {
            static::restoring(function (Model $model) {
                $model->clearByAttribute('deleted_by', null)
                    ->updateByAttribute('updated_by');
            });
        }
    }

    /**
     * Update by attribute
     *
     * @param string $attr
     * @param bool $force
     * @return void
     */
    public function updateByAttribute($attr, $value = null, $force = false)
    {
        if (auth()->check() && ($force || array_key_exists($attr, $this->attributes))) {
            $this->$attr = $value ?? auth()->user()->id;
        }

        return $this;
    }

    /**
     * Clear by attribute
     *
     * @param string $attr
     * @return void
     */
    public function clearByAttribute($attr)
    {
        if (auth()->check() && array_key_exists($attr, $this->attributes)) {
            $this->$attr = null;
        }

        return $this;
    }
}
