<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ManualController extends Controller
{
    /**
     * Display the system manual page.
     */
    public function index()
    {
        return view('help.manual');
    }
}
