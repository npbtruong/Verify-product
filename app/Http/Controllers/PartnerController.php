<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PartnerController extends Controller
{
    /**
     * Get all partners
     */
    public function index()
    {
        $partners = Partner::withCount('users')->get();
        
        return response()->json([
            'partners' => $partners
        ]);
    }

    /**
     * Get single partner
     */
    public function show($id)
    {
        $partner = Partner::with('users')->find($id);

        if (!$partner) {
            return response()->json(['message' => 'Partner không tồn tại'], 404);
        }

        return response()->json([
            'partner' => $partner
        ]);
    }

    /**
     * Create new partner (Admin or Partner only)
     */
    public function store(Request $request)
    {
        $currentUser = $request->user();

        // Kiểm tra quyền: chỉ admin hoặc partner mới có thể tạo partner
        if (!$currentUser->isAdmin() && !$currentUser->isPartner()) {
            return response()->json([
                'message' => 'Bạn không có quyền tạo Partner'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:255|unique:partners,domain',
            'brand' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $partner = Partner::create([
            'name' => $request->name,
            'domain' => $request->domain,
            'brand' => $request->brand,
        ]);

        return response()->json([
            'message' => 'Tạo Partner thành công',
            'partner' => $partner,
        ], 201);
    }

    /**
     * Update partner (Admin or Partner only)
     */
    public function update(Request $request, $id)
    {
        $currentUser = $request->user();

        // Kiểm tra quyền
        if (!$currentUser->isAdmin() && !$currentUser->isPartner()) {
            return response()->json([
                'message' => 'Bạn không có quyền cập nhật Partner'
            ], 403);
        }

        $partner = Partner::find($id);

        if (!$partner) {
            return response()->json(['message' => 'Partner không tồn tại'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'domain' => 'sometimes|string|max:255|unique:partners,domain,' . $id,
            'brand' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $partner->update($request->only(['name', 'domain', 'brand']));

        return response()->json([
            'message' => 'Cập nhật Partner thành công',
            'partner' => $partner,
        ]);
    }

    /**
     * Delete partner (Admin only)
     */
    public function destroy(Request $request, $id)
    {
        $currentUser = $request->user();

        // Chỉ admin mới có quyền xóa partner
        if (!$currentUser->isAdmin()) {
            return response()->json([
                'message' => 'Chỉ Admin mới có quyền xóa Partner'
            ], 403);
        }

        $partner = Partner::find($id);

        if (!$partner) {
            return response()->json(['message' => 'Partner không tồn tại'], 404);
        }

        // Xóa partner (user liên kết sẽ có partner_id = null do onDelete('set null'))
        $partner->delete();

        return response()->json([
            'message' => 'Xóa Partner thành công'
        ]);
    }
}
