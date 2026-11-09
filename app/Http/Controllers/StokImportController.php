<?php

namespace App\Http\Controllers;

use App\Exports\StokImportTemplate;
use App\Models\Barang;
use App\Services\StokImportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

class StokImportController extends Controller
{
    public function form()
    {
        return view('stok.import');
    }

    public function template()
    {
        return Excel::download(new StokImportTemplate, 'template_input_stok.xlsx');
    }

    public function proses(Request $request, StokImportService $stok)
    {
        $request->validate([
            'file'            => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
            'tanggal_default' => ['nullable', 'date'],
        ], [
            'file.required' => 'Pilih file Excel dulu.',
            'file.mimes'    => 'File harus berformat .xlsx, .xls, atau .csv.',
        ]);

        // Baca baris pertama sebagai judul kolom (kode_model, jumlah, harga_beli, tanggal_masuk)
        $sheets = Excel::toCollection(new class implements WithHeadingRow {}, $request->file('file'));
        $rows   = $sheets->first() ?? collect();

        $kodeSemua = $rows->pluck('kode_model')->filter()->map(fn ($k) => trim((string) $k))->unique();
        $barang    = Barang::whereIn('kode_model', $kodeSemua)->get()->keyBy('kode_model');

        $errors = [];
        $siap   = [];

        foreach ($rows as $i => $row) {
            $no = $i + 2; // baris 1 = judul kolom

            // Lewati baris yang benar-benar kosong
            if (collect($row)->filter(fn ($v) => $v !== null && $v !== '')->isEmpty()) {
                continue;
            }

            $kode   = trim((string) ($row['kode_model'] ?? ''));
            $jumlah = $row['jumlah'] ?? null;
            $harga  = $row['harga_beli'] ?? null;
            $b      = $barang[$kode] ?? null;
            $salah  = [];

            if ($kode === '' || ! $b) {
                $salah[] = "kode_model \"{$kode}\" tidak ditemukan di Data Barang";
            } elseif ($b->tanpa_stok) {
                $salah[] = "{$kode} adalah lensa custom (tanpa stok)";
            }
            if (! is_numeric($jumlah) || (float) $jumlah != floor((float) $jumlah) || (int) $jumlah < 1) {
                $salah[] = 'jumlah harus bilangan bulat minimal 1';
            }
            if (! is_numeric($harga) || (float) $harga < 0) {
                $salah[] = 'harga_beli harus angka (tanpa titik/koma ribuan) dan tidak negatif';
            }

            $tanggal = $this->bacaTanggal($row['tanggal_masuk'] ?? null, $request->input('tanggal_default'));
            if (! $tanggal) {
                $salah[] = 'tanggal_masuk kosong/tidak terbaca (contoh: 2026-11-07 14:30 atau 07/11/2026 14:30)';
            }

            if ($salah) {
                $errors[] = "Baris {$no}: " . implode('; ', $salah) . '.';
                continue;
            }

            $siap[] = [
                'no'      => $no,
                'barang'  => $b,
                'jumlah'  => (int) $jumlah,
                'harga'   => (float) $harga,
                'tanggal' => $tanggal,
            ];
        }

        if (! $siap && ! $errors) {
            return back()->with('error', 'File tidak berisi data.');
        }

        // Semua baris harus benar. Kalau ada yang salah, tidak ada yang disimpan.
        if ($errors) {
            return back()->with('daftarError', $errors);
        }

        // Proses dari tanggal paling lama supaya saldo mutasi berurutan
        usort($siap, fn ($a, $b) => [$a['tanggal'], $a['no']] <=> [$b['tanggal'], $b['no']]);

        try {
            DB::transaction(function () use ($siap, $stok) {
                foreach ($siap as $r) {
                    $stok->masukkan($r['barang']->id, $r['jumlah'], $r['harga'], $r['tanggal'], Auth::id());
                }
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('stok.import')
            ->with('success', count($siap) . ' baris stok berhasil dimasukkan.');
    }

    /**
     * Membaca tanggal dari sel Excel. Format teks dibaca sebagai hari/bulan/tahun (07/11/2026 = 7 November),
     * bukan format Amerika. Sel bertipe tanggal Excel (angka serial) juga didukung.
     */
    private function bacaTanggal($nilai, ?string $default): ?Carbon
    {
        if ($nilai === null || $nilai === '') {
            return $default ? Carbon::parse($default) : null;
        }

        if (is_numeric($nilai)) {
            $dt = ExcelDate::excelToDateTimeObject((float) $nilai);

            // Ambil jam dinding apa adanya, tanpa geser zona waktu
            return Carbon::createFromFormat('Y-m-d H:i:s', $dt->format('Y-m-d H:i:s'));
        }

        $teks = trim((string) $nilai);

        foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d', 'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'd-m-Y H:i', 'd-m-Y'] as $format) {
            try {
                $c = Carbon::createFromFormat($format, $teks);
                if ($c && $c->format($format) === $teks) {
                    // Format tanpa jam: dianggap jam 08:00 (ubah sesuai kebutuhan)
                    return str_contains($format, 'H') ? $c : $c->setTime(8, 0);
                }
            } catch (\Throwable $e) {
                // coba format berikutnya
            }
        }

        return null;
    }
}