<?php

namespace App\Http\Controllers;

use App\Models\TemplateNotifikasi;
use Illuminate\Http\Request;

class TemplateNotifikasiController extends Controller
{
    public function index()
    {
        $templates = [];
        foreach (TemplateNotifikasi::DAFTAR as $kode => $def) {
            $templates[$kode] = TemplateNotifikasi::ambil($kode);
        }

        return view('notifikasi.template', [
            'templates' => $templates,
            'daftar' => TemplateNotifikasi::DAFTAR,
            'penanda' => TemplateNotifikasi::PENANDA,
            'contoh' => TemplateNotifikasi::CONTOH,
        ]);
    }

    public function update(Request $request, string $kode)
    {
        abort_unless(isset(TemplateNotifikasi::DAFTAR[$kode]), 404);

        $request->validate([
            'isi' => ['required', 'string', 'max:1000'],
        ], [
            'isi.required' => 'Isi template tidak boleh kosong.',
            'isi.max' => 'Isi template maksimal 1000 karakter.',
        ]);

        $isi = str_replace(["\r\n", "\r"], "\n", trim($request->input('isi')));

        // Penanda yang tidak dikenal (salah ketik) ditolak
        preg_match_all('/\{([a-zA-Z_]+)\}/', $isi, $cocok);
        $tidakDikenal = array_values(array_diff($cocok[1], array_keys(TemplateNotifikasi::PENANDA)));
        if ($tidakDikenal) {
            $daftarSalah = implode(', ', array_map(fn ($p) => '{' . $p . '}', array_unique($tidakDikenal)));
            return back()->withInput()->withErrors([
                'isi' => 'Penanda tidak dikenal: ' . $daftarSalah . '. Pakai tombol penanda di bawah kotak isi.',
            ]);
        }

        // Penanda wajib untuk template ini
        // foreach (TemplateNotifikasi::DAFTAR[$kode]['wajib'] as $wajib) {
        //     if (! str_contains($isi, '{' . $wajib . '}')) {
        //         return back()->withInput()->withErrors([
        //             'isi' => 'Template ini wajib memuat {' . $wajib . '}.',
        //         ]);
        //     }
        // }

        $template = TemplateNotifikasi::ambil($kode);
        $template->isi = $isi;
        $template->save();

        return redirect()->route('notifikasi.template')
            ->with('success', 'Template "' . $template->nama . '" berhasil disimpan.');
    }

    public function reset(string $kode)
    {
        abort_unless(isset(TemplateNotifikasi::DAFTAR[$kode]), 404);

        $template = TemplateNotifikasi::ambil($kode);
        $template->isi = TemplateNotifikasi::DAFTAR[$kode]['isi'];
        $template->save();

        return redirect()->route('notifikasi.template')
            ->with('success', 'Template "' . $template->nama . '" dikembalikan ke teks bawaan.');
    }
}