<?php

namespace App\Providers;

use App\Models\Barang;
use App\Models\PesananKacamata;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\View\Composers\StatusBarComposer;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Batas stok "menipis": stok <= 5. Angka ini asumsi saya, ubah sesuai kebutuhan toko.
        $batas = 5;
        View::share('batasStok', $batas);

        View::composer('layouts.app', function ($view) use ($batas) {
            $view->with([
                'stokMenipis'  => Barang::where('stok_tersedia', '<=', $batas)->count(),
                'pesananAktif' => PesananKacamata::whereIn('status', ['menunggu', 'dikerjakan'])->count(),
            ]);
        });
        View::composer('layouts.app', StatusBarComposer::class);
    }
}