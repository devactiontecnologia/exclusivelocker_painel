<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;

class LogController extends Controller
{
    public function index()
    {
        return view('logs.index', ['rows' => SystemLog::query()->latest()->paginate(40)]);
    }
}
