<?php

namespace Xpressengine\Plugins\XeroCommerce\Controllers\Settings;

use DB;
use Exception;
use XeLang;
use XeConfig;
use XePresenter;
use XeCategory;
use Xpressengine\Category\Models\Category;
use Xpressengine\Plugins\XeroCommerce\Plugin;
use Xpressengine\Http\Request;
use Xpressengine\Support\Caster;
use Xpressengine\Support\Exceptions\InvalidArgumentHttpException;

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
	
    /**
     * Store a item of the category.
     *
     * @param Request $request request
     * @param string  $id      identifier
     * @return \Xpressengine\Presenter\Presentable
     * @throws Exception
     */
    public function storeItem(Request $request, $id)
    {
        /** @var Category $category */
        $category = XeCategory::cates()->find($id);

        DB::beginTransaction();

        try {
            /** @var CategoryItem $item */
            $item = XeCategory::createItem($category, $request->all());
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
        DB::commit();

        $multiLang = XeLang::getPreprocessorValues($request->all(), session()->get('locale'));
        $item->readableWord = $multiLang['word'];

        return XePresenter::makeApi($item->toArray());
    }

    /**
     * Update a item of the category.
     *
     * @param Request $request request
     * @param string  $id      identifier
     * @return \Xpressengine\Presenter\Presentable
     */
    public function updateItem(Request $request, $id)
    {
        /** @var CategoryItem $item */
        $item = XeCategory::items()->find($request->get('id'));
        if (!$item || $item->category->id !== Caster::cast($id)) {
            throw new InvalidArgumentHttpException;
        }

        XeCategory::updateItem($item, $request->all());

        $multiLang = XeLang::getPreprocessorValues($request->all(), session()->get('locale'));
        $item->readableWord = $multiLang['word'];

        return XePresenter::makeApi($item->toArray());
    }

    /**
     * Delete a item of the category.
     *
     * @param Request $request request
     * @param string  $id      identifier
     * @param bool    $force   하위카테고리 삭제 유무(true=>하위카테고리까지 삭제)
     * @return \Xpressengine\Presenter\Presentable
     * @throws Exception
     */
    public function destroyItem(Request $request, $id, $force = false)
    {
        /** @var CategoryItem $item */
        $item = XeCategory::items()->find($request->get('id'));
        if (!$item || $item->category->id !== Caster::cast($id)) {
            throw new InvalidArgumentHttpException;
        }

        DB::beginTransaction();

        try {
            XeCategory::deleteItem($item, $force);
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
        DB::commit();

        return XePresenter::makeApi([]);
    }

    protected function saveImage($imageParm, Category $newCategory, $key = null)
    {
        $file = XeStorage::upload($imageParm, 'public/xero_commerce/product');
        $imageFile = XeMedia::make($file);
        XeMedia::createThumbnails($imageFile, 'widen', config('xe.media.thumbnail.dimensions'));
        if (is_null($key)) {
            $newProduct->images()->attach($imageFile->id);
        } else {
            if ($existImage = $newProduct->images->get($key)) {
                $newProduct->images()->updateExistingPivot($existImage->id, ['image_id' => $imageFile->id]);
            }else{
                $newProduct->images()->attach($imageFile->id);
            }
        }

        return $imageFile;
    }

    protected function removeImage(Product $product, $key)
    {
        $image = $product->images->get($key);
        $product->images()->detach($image->id);
    }
	
}
