<?php

namespace App\View\Composers;

use App\Models\Barang;
use Illuminate\View\View;

class StatusBarComposer
{
    public function compose(View $view): void
    {
        // Halaman login tidak perlu angka ini
        if (!auth()->check()) {
            return;
        }

        $view->with([
            'jumlahStokMenipis'  => Barang::menipis()->count(),
            'jumlahPesananAktif' => $this->hitungPesananAktif(),
        ]);
    }

    /**
     * Modul Pesanan Kacamata belum dibuat, jadi sementara 0.
     * Setelah tabel pesanan ada, isi dengan hitungan pesanan yang belum selesai/batal, misalnya:
     * return PesananKacamata::whereNotIn('status', ['selesai', 'batal'])->count();
     */
    private function hitungPesananAktif(): int
    {
        return 0;
    }
}