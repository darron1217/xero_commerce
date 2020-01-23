<?php

namespace Xpressengine\Plugins\XeroCommerce\Models;

use App\Facades\XeMedia;
use Illuminate\Support\Facades\Auth;
use Xpressengine\Database\Eloquent\DynamicModel;

class Category extends \Xpressengine\Category\Models\Category
{

    public function images()
    {
        return $this->morphToMany(\Xpressengine\Media\Models\Image::class, 'imagable', 'xero_commerce_images');
    }

    function getImages()
    {
        if ($this->images->count() === 0) {
            return collect([asset('/assets/core/common/img/default_image_1200x800.jpg')]);
        }

        return $this->images->map(function ($item) {
            return XeMedia::images()->getThumbnail($item, 'widen', 'B')->url();
        });
    }

