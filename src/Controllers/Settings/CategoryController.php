<?php

namespace Xpressengine\Plugins\XeroCommerce\Controllers\Settings;

use DB;
use Exception;
use XeLang;
use XeConfig;
use XePresenter;
use XeCategory;
use XeStorage;
use XeMedia;
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
        $item = \Xpressengine\Plugins\XeroCommerce\Models\CategoryItem::find($request->get('id'));
        if (!$item || $item->category->id !== Caster::cast($id)) {
            throw new InvalidArgumentHttpException;
        }

        XeCategory::updateItem($item, $request->all());

        $multiLang = XeLang::getPreprocessorValues($request->all(), session()->get('locale'));
        $item->readableWord = $multiLang['word'];

        // 이미지 업로드
        if ($image = $request->get('image')) {
            if ($image == '__delete_file__') {
                $this->removeImage($item);
            } else {
                $this->saveImage($image, $item);
            }
        }

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
        $item = \Xpressengine\Plugins\XeroCommerce\Models\CategoryItem::find($request->get('id'));
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

    /**
     * Get children of a item.
     *
     * @param Request $request request
     * @param string  $id      identifier
     * @return \Xpressengine\Presenter\Presentable
     */
    public function children(Request $request, $id)
    {
        if ($request->get('id') === null) {
            $children = XeCategory::cates()->find($id)->getProgenitors();
        } else {
            /** @var CategoryItem $item */
            $item = \Xpressengine\Plugins\XeroCommerce\Models\CategoryItem::find($request->get('id'));
            if (!$item || $item->category->id !== Caster::cast($id)) {
                throw new InvalidArgumentHttpException;
            }

            $children = $item->getChildren();
        }

        foreach ($children as $child) {
            // 이미지 호출
            $child->image;
            $child->readableWord = xe_trans($child->word);
        }

        return XePresenter::makeApi($children->toArray());
    }

    protected function saveImage($imageParm, \Xpressengine\Plugins\XeroCommerce\Models\CategoryItem $categoryItem)
    {
        $file = XeStorage::upload($imageParm, 'public/xero_commerce/category');
        $imageFile = XeMedia::make($file);
        XeMedia::createThumbnails($imageFile, 'widen', config('xe.media.thumbnail.dimensions'));
        if ($existImage = $categoryItem->image) {
            $categoryItem->image()->updateExistingPivot($existImage->id, ['image_id' => $imageFile->id]);
        } else {
            $categoryItem->image()->save(['image_id' => $imageFile->id]);
        }

        return $imageFile;
    }

    protected function removeImage(\Xpressengine\Plugins\XeroCommerce\Models\CategoryItem $category)
    {
        if($image = $category->image) {
            $category->image->delete();
        }
    }

}
