<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PromoCodeController extends Controller
{
    public function index()
    {
        $codes = PromoCode::with('creator')->latest()->get();

        return view('admin.promo-codes.index', compact('codes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('promo_codes', 'code')],
            'discount_percent' => 'required|integer|min:1|max:100',
            'max_uses' => 'required|integer|min:1|max:10000',
            'expires_at' => 'nullable|date|after:now',
            'note' => 'nullable|string|max:500',
        ]);

        $code = Str::upper(trim($data['code'] ?: 'MJA-' . Str::upper(Str::random(8))));
        if (PromoCode::where('code', $code)->exists()) {
            return back()->withInput()->withErrors(['code' => 'Ce code existe déjà.']);
        }

        PromoCode::create([
            ...$data,
            'code' => $code,
            'active' => true,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', "Code promotionnel « {$code} » créé.");
    }

    public function update(Request $request, PromoCode $promoCode)
    {
        $data = $request->validate([
            'active' => 'required|boolean',
        ]);
        $promoCode->update($data);

        return back()->with('success', $promoCode->active ? 'Code activé.' : 'Code désactivé.');
    }
}
