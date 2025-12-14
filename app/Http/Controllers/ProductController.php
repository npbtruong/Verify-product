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
            'tag_id' => 'required|string|max:255|unique:products,tag_id',
            'image' => 'required|image|mimes:jpeg,jpg,png,gif|max:5120', // max 5MB
            'describe' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Upload image
            $image = $request->file('image');
            $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('products', $imageName, 'public');
            $imageUrl = Storage::url($imagePath);

            // Create product
            $product = Product::create([
                'tag_id' => $request->tag_id,
                'image_url' => $imageUrl,
                'describe' => $request->describe,
                'uploaded_by' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Sản phẩm đã được tạo thành công',
                'product' => $product->load('user:id,name,email'),
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
            'tag_id' => 'sometimes|string|max:255|unique:products,tag_id,' . $id,
            'image' => 'sometimes|image|mimes:jpeg,jpg,png,gif|max:5120',
            'describe' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $updateData = [];

            // Update tag_id if provided
            if ($request->has('tag_id')) {
                $updateData['tag_id'] = $request->tag_id;
            }

            // Update describe if provided
            if ($request->has('describe')) {
                $updateData['describe'] = $request->describe;
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
}
