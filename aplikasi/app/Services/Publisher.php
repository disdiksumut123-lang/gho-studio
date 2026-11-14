<?php

namespace App\Services;

use App\Models\SocialAccount;
use App\Models\SocialPost;

/**
 * Menerbitkan satu konten ke banyak platform.
 * Setiap platform butuh aplikasi resmi yang disetujui platform itu (Meta, Google, TikTok, dll.)
 * dan akun yang dihubungkan. Selama belum terhubung, statusnya "Belum terhubung" dan konten tetap tersimpan.
 */
class Publisher
{
    public function publish(SocialPost $post): SocialPost
    {
        $res = $post->results ?? [];
        foreach ($post->platforms ?? [] as $p) {
            if (($res[$p]['status'] ?? null) === 'terbit') continue;
            $meta = SocialPost::PLATFORMS[$p] ?? null;
            if (! $meta) continue;
            if ($meta[3] === 'video' && $post->media_type !== 'video') { $res[$p] = ['status' => 'dilewati', 'note' => __(':p hanya menerima video.', ['p' => $meta[0]])]; continue; }
            $acc = SocialAccount::where(['platform' => $p, 'enabled' => true])->first();
            if (! $acc || ! $acc->token) { $res[$p] = ['status' => 'belum_terhubung', 'note' => __('Hubungkan akun :p dulu.', ['p' => $meta[0]])]; continue; }
            try {
                $res[$p] = $this->send($p, $acc, $post);
            } catch (\Throwable $e) {
                $res[$p] = ['status' => 'gagal', 'note' => mb_substr($e->getMessage(), 0, 200)];
            }
        }
        $states = array_column($res, 'status');
        $status = $states && count(array_filter($states, fn ($s) => $s === 'terbit')) === count($states) ? 'selesai' : (in_array('terbit', $states, true) ? 'sebagian' : 'perlu_tindakan');
        $post->update(['results' => $res, 'status' => $status]);
        return $post;
    }

    /** Pengiriman ke API tiap platform dipasang di tahap integrasi (butuh aplikasi resmi yang disetujui). */
    private function send(string $platform, SocialAccount $acc, SocialPost $post): array
    {
        return ['status' => 'menunggu_integrasi', 'note' => __('Akun tersimpan. Pengiriman otomatis aktif setelah aplikasi resmi platform disetujui.')];
    }
}
