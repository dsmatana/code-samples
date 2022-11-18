<?php

namespace App\Traits\Banner;

use App\Category;
use App\Product;

/**
 * @author Adam Ondrejkovic
 * Created by PhpStorm.
 * Date: 29/01/2020
 * Time: 14:34
 */

trait SeederMethods {

	/**
	 * @return string
	 * @author Adam Ondrejkovic
	 * For seeders only
	 */
	public static function randomType()
	{
		return self::LINK_ENTITIES[array_rand(self::LINK_ENTITIES)];
	}

	/**
	 * @author Adam Ondrejkovic
	 * For seeders only
	 */
	public function createDummyEntity()
	{
		$randomType = self::randomType();

		switch ($randomType) {
			case self::LINK_ENTITY_PRODUCT:
				return Product::create([
					'list_id' => dummy()->randomId(products()->dummyData()),
				])->banner()->save($this);
				break;

			case self::LINK_ENTITY_CATEGORY:
				return Category::create([
					'list_id' => dummy()->randomId(categories()->dummyData()),
				])->banner()->save($this);

				break;
		}
	}
}