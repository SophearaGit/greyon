<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesAdminPanel;
use App\Http\Controllers\Concerns\ManagesCatalogItems;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Client requirements doc (2026-10-04), item 5 - "Products": a new
 * admin-sidebar section; hotels select multiple products (many-to-many,
 * see HotelController's `productIds`). Perm `products`.
 *
 * The doc gives no field spec for a product. Implemented as an optional
 * priced add-on (image/price/currency all nullable) - confirm with the
 * client. Same shared-catalog / delete-is-global-seat-only rules as
 * ServiceController.
 */
class ProductController extends Controller
{
    use AuthorizesAdminPanel;
    use ManagesCatalogItems;

    public function __construct(private readonly AccessService $access) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->catalogIndexQuery($request, Product::class)->get();

        return response()->json(['products' => ProductResource::collection($products)]);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json(['product' => new ProductResource($product->loadCount('hotels'))]);
    }

    public function store(Request $request): JsonResponse
    {
        $product = Product::create($this->validated($request));

        // refresh() so DB defaults (status, sort_order) are in the response.
        return response()->json(['product' => new ProductResource($product->refresh()->loadCount('hotels'))], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $product->update($this->validated($request, $product));

        return response()->json(['product' => new ProductResource($product->refresh()->loadCount('hotels'))]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->assertGlobalSeat($request);

        $product->delete();

        return response()->json(['message' => 'Product deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => [$product ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('products', 'slug')->ignore($product?->id),
            ],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'imageUrl' => ['nullable', 'string', 'max:2048'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'currency' => ['nullable', 'string', 'size:3', 'alpha'],
            'sortOrder' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:65535'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
        ]);

        if ($product && array_key_exists('slug', $data) && blank($data['slug'])) {
            unset($data['slug']);
        }

        if (isset($data['currency'])) {
            $data['currency'] = strtoupper($data['currency']);
        }

        return $this->normalizeCatalogPayload($data, Product::class, $product);
    }
}
