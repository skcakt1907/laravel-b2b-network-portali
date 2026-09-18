<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\MembershipApplication;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Davet bagi ile uyelik basvurusu.
 *
 * Kapali devre: form yalnizca gecerli bir davet kodu ile acilir.
 * Kod yoksa, kullanilmissa veya suresi dolmussa form hic gosterilmez.
 */
class ApplicationController extends Controller
{
    public function show(string $code): View
    {
        $davet = $this->daveti($code);

        return view('davet.form', [
            'davet' => $davet,
            'markalar' => Profile::MARKA_ETIKETLERI,
        ]);
    }

    public function store(Request $request, string $code): RedirectResponse
    {
        $davet = $this->daveti($code);

        $veri = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required', 'email', 'max:180',
                // Zaten uye olan veya bekleyen basvurusu olan adres tekrar basvuramaz
                Rule::unique('users', 'email'),
                Rule::unique('membership_applications', 'email')
                    ->where(fn ($q) => $q->where('status', 'beklemede')),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'company_name' => ['required', 'string', 'max:160'],
            'sector' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:80'],
            'brand' => ['nullable', Rule::in(array_keys(Profile::MARKA_ETIKETLERI))],
            'reference_name' => ['nullable', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:1000'],
        ], [
            'email.unique' => 'Bu e-posta adresi icin zaten bir kayit veya bekleyen basvuru var.',
        ], [
            'reference_name' => 'referans',
        ]);

        // status, reviewed_* ve created_user_id bilerek $fillable disindadir;
        // basvuru her zaman 'beklemede' olarak dogar.
        MembershipApplication::create($veri + ['invitation_id' => $davet->id]);

        return redirect()->route('davet.tesekkur');
    }

    public function tesekkur(): View
    {
        return view('davet.tesekkur');
    }

    /**
     * Daveti dogrular. Gecersiz her durumda 404 doner:
     * kodun var olup olmadigini disaridan anlamak mumkun olmamali.
     */
    private function daveti(string $code): Invitation
    {
        $davet = Invitation::where('code', $code)->first();

        if ($davet === null || ! $davet->isKullanilabilir()) {
            abort(404);
        }

        return $davet;
    }
}
