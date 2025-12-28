<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Get all products or filter by current user
     */
    public function index(Request $request)
    {
        $query = Product::with('user:id,name,email');

        // Filter by current user if requested
        if ($request->has('my_products')) {
            $query->where('uploaded_by', $request->user()->id);
        }

        // Filter by specific user_id (admin feature)
        if ($request->has('user_id')) {
            $query->where('uploaded_by', $request->user_id);
        }

        // Search by tag_id
        if ($request->has('tag_id')) {
            $query->where('tag_id', 'LIKE', '%' . $request->tag_id . '%');
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($products);
    }

    /**
     * Get single product by ID
     */
    public function show($id)
    {
        $product = Product::with('user:id,name,email')->findOrFail($id);

        return response()->json($product);
    }

    /**
     * Upload product with image
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,jpg,png,gif|max:5120', // max 5MB
            'describe' => 'nullable|string|max:1000',
            'owner_name' => 'nullable|string|max:255',
            'owner_email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Generate unique tag_id automatically
            $tagId = Product::generateUniqueTagId();

            // Upload image
            $image = $request->file('image');
            $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('products', $imageName, 'public');
            $imageUrl = Storage::url($imagePath);

            // Create product
            $product = Product::create([
                'tag_id' => $tagId,
                'image_url' => $imageUrl,
                'describe' => $request->describe,
                'uploaded_by' => $request->user()->id,
                'owner_name' => $request->owner_name,
                'owner_email' => $request->owner_email,
            ]);

            // Generate NFC URL
            $nfcUrl = config('app.url') . '/api/nfc/' . $product->tag_id;

            return response()->json([
                'message' => 'Sản phẩm đã được tạo thành công',
                'product' => $product->load('user:id,name,email'),
                'nfc_url' => $nfcUrl,
                'nfc_instructions' => 'Ghi URL này vào thẻ NFC để khách hàng có thể scan',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi upload',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update product
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // Check authorization - only owner can update
        if ($product->uploaded_by !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền cập nhật sản phẩm này'], 403);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'sometimes|image|mimes:jpeg,jpg,png,gif|max:5120',
            'describe' => 'nullable|string|max:1000',
            'owner_name' => 'nullable|string|max:255',
            'owner_email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $updateData = [];

            // Note: tag_id KHÔNG THỂ sửa - đã được tự động generate và phải cố định

            // Update describe if provided
            if ($request->has('describe')) {
                $updateData['describe'] = $request->describe;
            }

            // Update owner info if provided
            if ($request->has('owner_name')) {
                $updateData['owner_name'] = $request->owner_name;
            }

            if ($request->has('owner_email')) {
                $updateData['owner_email'] = $request->owner_email;
            }

            // Update image if provided
            if ($request->hasFile('image')) {
                // Delete old image
                $oldImagePath = str_replace('/storage/', '', $product->image_url);
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }

                // Upload new image
                $image = $request->file('image');
                $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('products', $imageName, 'public');
                $updateData['image_url'] = Storage::url($imagePath);
            }

            $product->update($updateData);

            return response()->json([
                'message' => 'Sản phẩm đã được cập nhật',
                'product' => $product->load('user:id,name,email'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi cập nhật',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete product and its image
     */
    public function destroy(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // Check authorization - only owner can delete
        if ($product->uploaded_by !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền xóa sản phẩm này'], 403);
        }

        try {
            // Delete image from storage
            $imagePath = str_replace('/storage/', '', $product->image_url);
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            // Delete product from database
            $product->delete();

            return response()->json([
                'message' => 'Sản phẩm đã được xóa thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi xóa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's products statistics
     */
    public function statistics(Request $request)
    {
        $userId = $request->user()->id;
        
        $stats = [
            'total_products' => Product::where('uploaded_by', $userId)->count(),
            'recent_products' => Product::where('uploaded_by', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json($stats);
    }

    // ========================================
    // PUBLIC NFC ROUTES - Không cần authentication
    // ========================================

    /**
     * Get product by tag_id (Public - for NFC scan)
     * URL: GET /api/nfc/{tag_id}
     */
    public function getByTag($tagId)
    {
        $product = Product::where('tag_id', $tagId)
            ->with('user:id,name,email,partner_id', 'user.partner:id,name,domain,brand')
            ->firstOrFail();

        $uploadedBy = [
            'name' => $product->user->name,
            'email' => $product->user->email,
        ];

        // Thêm thông tin partner nếu có
        if ($product->user->partner) {
            $uploadedBy['partner'] = [
                'id' => $product->user->partner->id,
                'name' => $product->user->partner->name,
                'domain' => $product->user->partner->domain,
                'brand' => $product->user->partner->brand,
            ];
        }

        return response()->json([
            'product' => [
                'tag_id' => $product->tag_id,
                'image_url' => $product->image_url,
                'describe' => $product->describe,
                'owner_name' => $product->owner_name,
                'owner_email' => $product->owner_email,
                'created_at' => $product->created_at,
                'uploaded_by' => $uploadedBy,
            ],
        ]);
    }

    /**
     * Update owner information (Public - for NFC scan only)
     * URL: PUT /api/nfc/{tag_id}/owner
     * 
     * Chỉ cho phép update owner_name và owner_email
     * Không cho phép sửa ảnh, tag_id, describe
     */
    public function updateOwner(Request $request, $tagId)
    {
        $product = Product::where('tag_id', $tagId)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $product->update([
                'owner_name' => $request->owner_name,
                'owner_email' => $request->owner_email,
            ]);

            return response()->json([
                'message' => 'Cập nhật thông tin chủ sở hữu thành công',
                'product' => [
                    'tag_id' => $product->tag_id,
                    'owner_name' => $product->owner_name,
                    'owner_email' => $product->owner_email,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi cập nhật',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
