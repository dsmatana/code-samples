<?php

namespace App\Traits;

use App\Segment;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Segmentable {
	public function segments(): MorphToMany {
		return $this->morphToMany(Segment::class, 'segmentable', 'segments_models');
	}
}
