<?php

namespace Xpressengine\Plugins\XeroCommerce\Controllers;

use App\Http\Controllers\Controller;
use Xpressengine\Http\Request;
use Xpressengine\Plugins\XeroCommerce\Components\Modules\XeroCommerceModule;
use Xpressengine\Plugins\XeroCommerce\Models\Product;
use Xpressengine\Plugins\XeroCommerce\Plugin;
use Xpressengine\Plugins\XeroCommerce\Services\CartService;
use Xpressengine\Plugins\XeroCommerce\Services\FeedbackService;
use Xpressengine\Plugins\XeroCommerce\Services\ProductCategoryService;
use Xpressengine\Plugins\XeroCommerce\Services\ProductService;
use Xpressengine\Plugins\XeroCommerce\Services\ProductSlugService;
use Xpressengine\Plugins\XeroCommerce\Services\QnaService;
use Xpressengine\Plugins\XeroCommerce\Services\WishService;
use Xpressengine\Routing\InstanceConfig;

class ProductController extends XeroCommerceBasicController
{
    /** @var ProductService $productService */
    protected $productService;

    /** @var InstanceConfig */
    protected $instanceConfig;

    /** @var string $instanceId */
    private $instanceId;

    public function __construct()
    {
        parent::__construct();
        \XePresenter::setSkinTargetId(XeroCommerceModule::getId());

        $this->productService = new ProductService();

        $this->instanceConfig = InstanceConfig::instance();
        $this->instanceId = $this->instanceConfig->getInstanceId();
    }

    public function index(Request $request)
    {
        $moduleInstanceId = $this->instanceId;
        $config = \XeConfig::get(sprintf('%s.%s', Plugin::getId(), $this->instanceId));
        $products = $this->productService->getProducts($request, $config);

        return \XePresenter::make('product.index', ['products' => $products, 'instanceId' => $moduleInstanceId]);
    }

    public function show(Request $request, $strSlug)
    {
        $productId = ProductSlugService::getProductId($strSlug);

        $product = $this->productService->getProduct($productId);

        $categoryService = new ProductCategoryService();
        $category = $categoryService->getCategoryTree();

        if ($product == null) {
            return redirect()->to(instance_route('xero_commerce::product.index', [], $this->instanceId))
                ->with('alert', ['type' => 'danger', 'message' => '존재하지 않는 상품입니다.']);
        }

		// 최근 본 상품 기능
		$productIds = $request->session()->get('recentProducts', []);
		$productIds[] = $productId;
		$productIds = array_slice($productIds, -10, 10);
		$request->session()->put('recentProducts', $productIds);
		
        return \XePresenter::make('product.show_new', ['product' => $product, 'category' => $category]);
    }

    public function cartAdd(Request $request, Product $product)
    {
        $cartService = new CartService();

        return $cartService->addList($request, $product);
    }

    public function wishToggle(Product $product)
    {
        $wishService = new WishService();

        return $wishService->toggle($product);
    }

    public function qnaAdd(Product $product, Request $request)
    {
        $qnaService = new QnaService();
        $qnaService->store($product, $request);
    }

    public function qnaLoad(Product $product)
    {
        $qnaService = new QnaService();
        return $qnaService->getTargetQna($product);
    }

    public function feedbackAdd(Product $product, Request $request)
    {
        $qnaService = new FeedbackService();
        $qnaService->store($product, $request);
    }

    public function feedbackLoad(Product $product)
    {
        $qnaService = new FeedbackService();
        return $qnaService->getTargetFeedback($product);
    }
}
