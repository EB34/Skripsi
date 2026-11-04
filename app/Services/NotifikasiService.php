<?php

namespace App\Services;

use App\Models\LogNotifikasi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Throwable;

class NotifikasiService
{
    /**
     * Catat lalu kirim pesan WhatsApp. Selalu mengembalikan baris log,
     * baik berhasil maupun gagal (tidak melempar exception).
     */
    public function kirim(
        string $tujuan,
        string $pesan,
        string $jenis = 'manual',
        ?int $customerId = null,
        ?string $refTipe = null,
        ?int $refId = null
    ): LogNotifikasi {
        $log = LogNotifikasi::create([
            'customer_id' => $customerId,
            'jenis' => $jenis,
            'tujuan' => self::normalisasiNomor($tujuan),
            'pesan' => $pesan,
            'status' => 'menunggu',
            'percobaan' => 0,
            'referensi_tipe' => $refTipe,
            'referensi_id' => $refId,
            'dibuat_oleh' => Auth::id(),
        ]);

        return $this->proses($log);
    }

    public function kirimUlang(LogNotifikasi $log): LogNotifikasi
    {
        return $this->proses($log);
    }

    private function proses(LogNotifikasi $log): LogNotifikasi
    {
        $log->percobaan = $log->percobaan + 1;

        if (! config('whatsapp.aktif')) {
            $log->status = 'gagal';
            $log->respons = 'Gateway WhatsApp dinonaktifkan (WA_GATEWAY_AKTIF=false).';
            $log->save();
            return $log;
        }

        if (! preg_match('/^62\d{8,13}$/', $log->tujuan)) {
            $log->status = 'gagal';
            $log->respons = 'Nomor tujuan tidak valid: ' . $log->tujuan;
            $log->save();
            return $log;
        }

        try {
            $respon = Http::withToken((string) config('whatsapp.token'))
                ->timeout((int) config('whatsapp.timeout'))
                ->acceptJson()
                ->post(rtrim((string) config('whatsapp.url'), '/') . '/send', [
                    'number' => $log->tujuan,
                    'message' => $log->pesan,
                ]);

            if ($respon->successful()) {
                $log->status = 'terkirim';
                $log->dikirim_pada = now();
                $log->respons = mb_substr($respon->body(), 0, 1000);
            } else {
                $log->status = 'gagal';
                $log->respons = 'HTTP ' . $respon->status() . ': ' . mb_substr($respon->body(), 0, 1000);
            }
        } catch (Throwable $e) {
            $log->status = 'gagal';
            $log->respons = mb_substr($e->getMessage(), 0, 1000);
        }

        $log->save();
        return $log;
    }

    /** 0812... / 812... / +62812... menjadi 62812... (sama dengan format customer.no_whatsapp). */
    public static function normalisasiNomor(string $nomor): string
    {
        $n = preg_replace('/\D+/', '', $nomor);

        if (str_starts_with($n, '0')) {
            return '62' . substr($n, 1);
        }
        if (str_starts_with($n, '8')) {
            return '62' . $n;
        }
        return $n;
    }
}