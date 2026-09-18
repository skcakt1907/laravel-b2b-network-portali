<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function index(): View
    {
        return view('yonetim.davetler.index', [
            'davetler' => Invitation::with(['inviter', 'createdUser'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $veri = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:180'],
            'gun' => ['required', 'integer', 'min:1', 'max:365'],
        ], [], ['gun' => 'gecerlilik suresi']);

        $davet = new Invitation();
        $davet->type = 'uye';
        $davet->name = $veri['name'] ?? null;
        $davet->email = $veri['email'] ?? null;
        // Form alani metin gelir ("14"); Carbon tam sayi bekler
        $davet->expires_at = now()->addDays((int) $veri['gun']);

        // code ve invited_by bilerek $fillable disindadir
        $davet->code = Invitation::yeniKod();
        $davet->invited_by = $request->user()->id;
        $davet->save();

        return redirect()
            ->route('yonetim.davetler.index')
            ->with('basari', 'Davet bagi olusturuldu. Adayin e-postasina siz iletmelisiniz.');
    }

    public function destroy(Invitation $davet): RedirectResponse
    {
        if ($davet->used_at !== null) {
            return back()->with('hata', 'Kullanilmis davet silinemez; kayit izi olarak kalir.');
        }

        $davet->delete();

        return back()->with('bilgi', 'Davet bagi iptal edildi.');
    }
}
