<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

/**
 * Trait for eager loading of model relations by include array.
 * Usage: Model::withIncludes($request)
 */
trait HasIncludes
{

    /**
     * Eloquent builder scope.
     *
     * @param /Illuminate\Database\Eloquent\Builder $qb
     * @param /Illuminate\Http\Request $request
     * @return void
     */
    public function scopeWithIncludes(Builder &$qb, Request $request)
    {
        $includes = collect($request->input(config('fractal.auto_includes.request_key'), []));
        if (!$includes->isEmpty()) {
            $qb->with($includes->filter(function ($item) {
                return $this->_validateHasIncludes($this, explode('.', $item));
            })->all());
        }
    }

    /**
     * Recursively check for existing relations by includes array.
     *
     * @param /Illuminate\Database\Eloquent\Model $model
     * @param array $includes
     * @return bool
     */
    protected function _validateHasIncludes(Model $model, array $includes)
    {
        if (!method_exists($model, $includes[0])) {
            return false;
        }
        $relation =  $model->{$includes[0]}();
        if (!$relation instanceof Relation) {
            return false;
        }
        array_shift($includes);
        if (count($includes)) {
            if (!$this->_validateHasIncludes($relation->getModel(), $includes)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Query builder has eager load
     *
     * @param string $relation
     * @param /Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation $qb
     * @return bool
     */
    protected function hasEagerLoad(string $relation, &$qb)
    {
        return array_key_exists($relation, $qb->getEagerLoads());
    }
}
