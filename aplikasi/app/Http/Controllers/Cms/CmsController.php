<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\AiClient;
use App\Services\Publisher;
use Illuminate\Http\Request;

/** CMS (Content Management System): unggah sekali, terbit di banyak media sosial. */
class CmsController extends Controller
{
    public function index()
    {
        return view('cms.home', [
            'posts' => SocialPost::latest()->paginate(15),
            'accounts' => SocialAccount::all()->keyBy('platform'),
            'upcoming' => SocialPost::where('status', 'terjadwal')->orderBy('scheduled_at')->limit(7)->get(),
        ]);
    }

    public function create()
    {
        return view('cms.compose', ['post' => new SocialPost(['platforms' => ['yt', 'fb', 'ig', 'tt']]), 'accounts' => SocialAccount::all()->keyBy('platform')]);
    }

    public function edit(SocialPost $post)
    {
        return view('cms.compose', ['post' => $post, 'accounts' => SocialAccount::all()->keyBy('platform')]);
    }

    public function save(Request $r, ?SocialPost $post = null)
    {
        $d = $r->validate([
            'title' => 'required|max:200', 'caption' => 'nullable|string|max:20000',
            'platforms' => 'array', 'platforms.*' => 'in:'.implode(',', array_keys(SocialPost::PLATFORMS)),
            'captions' => 'array', 'scheduled_at' => 'nullable|date',
            'media' => 'nullable|file|max:512000|mimetypes:video/mp4,video/quicktime,video/webm,image/jpeg,image/png,image/webp',
            'action' => 'required|in:draf,jadwal,sekarang',
        ]);
        $post ??= new SocialPost(['created_by' => $r->user()->id]);
        $post->fill(['title' => $d['title'], 'caption' => $d['caption'] ?? '', 'platforms' => $d['platforms'] ?? [], 'captions' => array_filter($d['captions'] ?? []), 'scheduled_at' => $d['scheduled_at'] ?? null]);
        if ($f = $r->file('media')) {
            $post->media_path = $f->store('cms', 'local');
            $post->media_name = $f->getClientOriginalName();
            $post->media_type = str_starts_with($f->getMimeType(), 'video/') ? 'video' : 'gambar';
        }
        if ($d['action'] === 'jadwal' && ! $post->scheduled_at) return back()->withInput()->with('err', __('Isi waktu terbit untuk menjadwalkan.'));
        $post->status = match ($d['action']) { 'draf' => 'draf', 'jadwal' => 'terjadwal', default => 'diproses' };
        $post->save();
        if ($d['action'] === 'sekarang') {
            if (empty($post->platforms)) return back()->with('err', __('Pilih minimal satu platform.'));
            app(Publisher::class)->publish($post);
            return redirect()->route('cms.edit', $post)->with('ok', __('Diproses. Lihat status tiap platform di kanan.'));
        }
        return redirect()->route('cms.edit', $post)->with('ok', $post->status === 'terjadwal' ? __('Terjadwal :w.', ['w' => $post->scheduled_at->translatedFormat('d M Y H:i')]) : __('Draf disimpan.'));
    }

    public function update(Request $r, SocialPost $post) { return $this->save($r, $post); }

    public function destroy(SocialPost $post)
    {
        $post->delete();
        return redirect()->route('cms.home')->with('ok', __('Konten dihapus.'));
    }

    /** Sesuaikan caption utama untuk tiap platform. */
    public function adapt(Request $r)
    {
        $d = $r->validate(['caption' => 'required|string|min:10|max:8000', 'platforms' => 'required|array']);
        $list = collect($d['platforms'])->filter(fn ($p) => isset(SocialPost::PLATFORMS[$p]))
            ->map(fn ($p) => $p.' = '.SocialPost::PLATFORMS[$p][0].' (maks '.SocialPost::PLATFORMS[$p][4].' karakter)')->implode("\n");
        try {
            $out = AiClient::default()->ask(
                'Kamu pengelola media sosial lembaga pelatihan ASN. Tulis caption yang sopan, menarik, dan sesuai gaya tiap platform. Jangan mengarang fakta di luar teks.',
                "Caption utama:\n{$d['caption']}\n\nSesuaikan untuk platform berikut:\n{$list}\nYouTube: judul di baris pertama lalu deskripsi dan tagar. TikTok/Instagram: singkat, tagar di akhir. X: di bawah 280 karakter.\nJSON {\"captions\":{\"kode_platform\":\"caption\"}}",
                true, 'cms_caption');
            return response()->json(['captions' => $out['captions'] ?? []]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function accounts()
    {
        return view('cms.accounts', ['accounts' => SocialAccount::all()->keyBy('platform')]);
    }

    public function saveAccount(Request $r)
    {
        $d = $r->validate(['platform' => 'required|in:'.implode(',', array_keys(SocialPost::PLATFORMS)), 'name' => 'required|max:120', 'external_id' => 'nullable|max:120', 'token' => 'nullable|string|max:4000']);
        $acc = SocialAccount::firstOrNew(['platform' => $d['platform']]);
        $acc->fill(['name' => $d['name'], 'external_id' => $d['external_id'] ?? null, 'enabled' => true]);
        if (! empty($d['token'])) $acc->token = $d['token'];
        $acc->save();
        return back()->with('ok', __('Akun :p disimpan.', ['p' => SocialPost::PLATFORMS[$d['platform']][0]]));
    }

    public function removeAccount(SocialAccount $account)
    {
        $account->delete();
        return back()->with('ok', __('Akun diputus.'));
    }
}
