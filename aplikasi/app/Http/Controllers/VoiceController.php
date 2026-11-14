<?php

namespace App\Http\Controllers;

use App\Services\Voice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VoiceController extends Controller
{
    public function toText(Request $r)
    {
        $r->validate(['audio' => 'required|file|max:25600']);
        if (! Voice::enabled()) return response()->json(['error' => 'no_key'], 422);
        try {
            $f = $r->file('audio');
            return response()->json(['text' => Voice::transcribe($f->getRealPath(), $f->getMimeType() ?: 'audio/webm')]);
        } catch (\Throwable $e) {
            return response()->json(['error' => __('Suara belum bisa diubah menjadi teks.').' '.$e->getMessage()], 422);
        }
    }

    public function toSpeech(Request $r)
    {
        $r->validate(['text' => 'required|string|max:20000']);
        if (! Voice::enabled()) return response()->json(['error' => 'no_key'], 422);
        try {
            $file = Voice::speak(strip_tags($r->input('text')));
            return response()->file(Storage::disk('local')->path($file), ['Content-Type' => 'audio/mpeg']);
        } catch (\Throwable $e) {
            return response()->json(['error' => __('Suara belum bisa dibuat.').' '.$e->getMessage()], 422);
        }
    }
}
