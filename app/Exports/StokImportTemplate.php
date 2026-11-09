<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StokImportTemplate implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['kode_model', 'jumlah', 'harga_beli', 'tanggal_masuk'];
    }

    public function array(): array
    {
        return [
            ['FR-001', 10, 150000, '2026-11-07 14:30'],
            ['FR-002', 5, 175000, '2026-11-09 10:15'],
        ];
    }
}