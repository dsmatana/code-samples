<?php

namespace App\Transformers\Traits;

use App\Transformers\UserTransformer;
use Illuminate\Database\Eloquent\Model;

trait HasByIncludes {
	public function includeCreatedBy(Model $model) {
		return $model->created_by ? $this->item($model->createdBy, new UserTransformer) : $this->null();
	}

	public function includeUpdatedBy(Model $model) {
		return $model->updated_by ? $this->item($model->updatedBy, new UserTransformer) : $this->null();
	}

	public function includeDeletedBy(Model $model) {
		return $model->deleted_by ? $this->item($model->deletedBy, new UserTransformer) : $this->null();
	}
}
