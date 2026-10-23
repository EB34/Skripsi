<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PengelolaanStokController extends Controller
{
    // tab yg bs dipilih
    private const TAB = ['monitoring','mutasi','rusak'];

    public function index(Request $request)
    {
        $tab = $request->query('tab','monitoring');
    }
    
}
