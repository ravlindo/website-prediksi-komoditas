<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')->when($request->string('event')->toString(), fn ($q, $v) => $q->where('event', $v))->latest()->paginate(30)->withQueryString();

        return view('master.activity.index', compact('logs'));
    }
}
