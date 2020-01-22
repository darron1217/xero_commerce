<?php

namespace Xpressengine\Plugins\XeroCommerce\Controllers\Settings;

use XeConfig;
use XePresenter;
use Xpressengine\Category\Models\Category;
use Xpressengine\Plugins\XeroCommerce\Plugin;

class CategoryController extends SettingBaseController
{
    public function index()
    {
        $config = XeConfig::get(Plugin::getId());

        $category = Category::find($config->get('categoryId'));

        if ($category === null) {
            throw new \Exception;
        }

        return XePresenter::make('category.show', compact('category'));
    }
}
