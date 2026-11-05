<?php

namespace App\View\Composers;

use App\Models\Barang;
use App\Models\PesananKacamata;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StatusBarComposer
{
    public function compose(View $view): void
    {
        // Halaman login tidak perlu angka ini
        if (!Auth::check()) {
            return;
        }

        $view->with([
            'jumlahStokMenipis'  => Barang::menipis()->count(),
            'jumlahPesananAktif' => PesananKacamata::aktif()->count(),
        ]);
    }
}