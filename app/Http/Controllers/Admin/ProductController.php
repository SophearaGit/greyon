<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesAdminPanel;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Client requirements doc (2026-10-04), item 5 — "Products": a
 * developer/admin-managed catalog a hotel picks from (see
 * HotelController's `productIds`). Perm `products`
 * (`permission:products` on every route in routes/admin.php).
 *
 * Deliberately a near-identical twin of ServiceController — same flat,
 * shared-catalog shape (not scoped to one location/hotel), same
 * store()/update()-feature-gated-only / destroy()-global-seat-only
 * split, same cascadeOnDelete pivot so deleting a product doesn't
 * check whether any hotel still references it. See ServiceController's
 * docblock for the full reasoning; it all applies here unchanged.
 */
class ProductController extends Controller
{
    use AuthorizesAdminPanel;

    public function __construct(private readonly AccessService $access) {}

    public function index(): JsonResponse
    {
        $products = Product::orderBy('name')->get();

        return response()->json(['products' => ProductResource::collection($products)]);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json(['product' => new ProductResource($product)]);
    }

    public function store(Request $request): JsonResponse
    {
        $product = Product::create($this->validated($request));

        // Re-fetch so DB column defaults not present in the request
        // (e.g. status) are reflected in the response — Eloquent's
        // create() doesn't otherwise pick those up on the in-memory
        // instance.
        return response()->json(['product' => new ProductResource($product->refresh())], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $product->update($this->validated($request, $product));

        return response()->json(['product' => new ProductResource($product)]);
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
        return $request->validate([
            'name' => [$product ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => [
                $product ? 'sometimes' : 'required',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($product?->id),
            ],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
        ]);
    }
}
