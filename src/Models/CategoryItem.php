<?php

namespace Xpressengine\Plugins\XeroCommerce\Models;

use App\Facades\XeMedia;
use Illuminate\Support\Facades\Auth;
use Xpressengine\Database\Eloquent\DynamicModel;

class CategoryItem extends \Xpressengine\Category\Models\CategoryItem
{

    public function image()
    {
        return $this->morphOne(Image::class, 'imagable');
    }

    function getImage()
    {
        if (!$this->image) {
            return collect([asset('/assets/core/common/img/default_image_1200x800.jpg')]);
        }

        return XeMedia::images()->getThumbnail($this->image, 'widen', 'B')->url();
    }

}
