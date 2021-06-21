<?php

namespace GetCandy\Api\Core\Discounts\Models;

use GetCandy;
use GetCandy\Api\Core\Customers\Models\CustomerGroup;
use GetCandy\Api\Core\Products\Models\Product;
use GetCandy\Api\Core\Scaffold\BaseModel;

class DiscountCriteriaItem extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['type', 'value'];

    /**
     * The Hashid connection name for enconding the id.
     *
     * @var string
     */
    protected $hashids = 'main';

    public function set()
    {
        return $this->belongsTo(DiscountCriteriaSet::class, 'discount_criteria_set_id');
    }

    public function saveEligible($type, $id)
    {
        $relation = camel_case(str_plural($type));

        $typeModel = GetCandy::getUserModel();

        if (method_exists($this, $relation)) {
            $realId = (new $typeModel)->decodeId($id);
            $this->{$relation}()->sync($realId);
        }
    }

    public function saveEligibles($type, array $ids)
    {
        $relation = camel_case(str_plural($type));
        if (method_exists($this, $relation)) {
            $this->{$relation}()->sync($ids);
        }
    }

    public function check($user, $basket)
    {
        if ($this->type == 'product') {
            return $this->checkWithProduct($basket);
        }

        return false;
    }

    /**
     * Checks whether a product is eligible.
     *
     * @param  \GetCandy\Api\Core\Baskets\Models\Basket  $basket
     * @return bool
     */
    protected function checkWithProduct($basket)
    {
        // Get the criteria item products.
        $items = $this->products;

        // Get the main discount
        $lowerLimit = $this->value;

        $quantity = 0;

        foreach ($basket->lines as $line) {
            if ($items->contains($line->variant->product)) {
                $quantity += $line->quantity;
            }
        }

        return $quantity >= $lowerLimit;
    }

    /**
     * Get all of the owning commentable models.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function eligibles()
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphedByMany
     */
    public function products()
    {
        return $this->morphedByMany(Product::class, 'eligible', 'discount_criteria_models');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphedByMany
     */
    public function customerGroups()
    {
        return $this->morphedByMany(CustomerGroup::class, 'eligible', 'discount_criteria_models');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphedByMany
     */
    public function users()
    {
        return $this->morphedByMany(config('auth.providers.users.model'), 'eligible', 'discount_criteria_models');
    }
}
