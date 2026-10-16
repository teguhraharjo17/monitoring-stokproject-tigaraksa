<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HariLiburService
{
    const API_URL = 'http://192.168.0.8:8000/api/libur';
    const BEARER_TOKEN = 'goVEnw1rqdsZg6ZOFLrEanc8FuIEhxkklCA6P2MG';

    /**
     * Ambil seluruh data hari libur dari API (dengan cache 12 jam).
     *
     * @return array
     */
    public static function fetchAllLiburFromApi(): array
    {
        return Cache::remember('api_hari_libur_data_v1', now()->addHours(12), function () {
            try {
                $response = Http::withToken(self::BEARER_TOKEN)
                    ->timeout(3)
                    ->connectTimeout(2)
                    ->acceptJson()
                    ->get(self::API_URL, [
                        'per_page' => 200,
                    ]);

                if ($response->successful()) {
                    $json = $response->json();
                    return $json['data'] ?? [];
                }

                Log::warning('HariLiburService: API returned status ' . $response->status());
            } catch (\Throwable $e) {
                Log::warning('HariLiburService: Gagal menghubungi API libur: ' . $e->getMessage());
            }

            return [];
        });
    }

    /**
     * Dapatkan mapping status libur untuk bulan dan tahun tertentu.
     * Mengembalikan array keyed by tanggal (1..31):
     * [
     *    1 => [
     *        'is_libur' => true,
     *        'is_weekend' => false,
     *        'tipe' => 'LIBUR NASIONAL',
     *        'keterangan' => 'Tahun Baru 2026 Masehi (LIBUR NASIONAL)',
     *        'nama_hari' => 'Kamis'
     *    ],
     *    ...
     * ]
     *
     * @param int $bulan
     * @param int $tahun
     * @return array
     */
    public static function getLiburBulan(int $bulan, int $tahun): array
    {
        $jumlahHari = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;
        $apiLibur = self::fetchAllLiburFromApi();

        // Mapping API data by tanggal string 'YYYY-MM-DD'
        $apiMap = [];
        foreach ($apiLibur as $item) {
            if (!empty($item['tanggal'])) {
                try {
                    $tglStr = Carbon::parse($item['tanggal'])->toDateString();
                    $apiMap[$tglStr] = [
                        'jenis_kalender' => $item['jenis_kalender'] ?? 'PROJECT',
                        'jenis_libur' => trim($item['jenis_libur'] ?? 'LIBUR NASIONAL'),
                        'keterangan' => trim(preg_replace('/\s+/', ' ', $item['keterangan'] ?? '')),
                    ];
                } catch (\Throwable $e) {
                    // Abaikan format tanggal tidak valid
                }
            }
        }

        $result = [];
        for ($d = 1; $d <= $jumlahHari; $d++) {
            $date = Carbon::createFromDate($tahun, $bulan, $d);
            $dateStr = $date->toDateString();
            $dayOfWeek = $date->dayOfWeek; // 0 = Sunday, 6 = Saturday
            $isWeekend = ($dayOfWeek === 0 || $dayOfWeek === 6);
            $namaHari = $date->translatedFormat('l');

            if (isset($apiMap[$dateStr])) {
                $liburInfo = $apiMap[$dateStr];
                $result[$d] = [
                    'is_libur' => true,
                    'is_weekend' => $isWeekend,
                    'tipe' => $liburInfo['jenis_libur'],
                    'keterangan' => $liburInfo['keterangan'] . ($liburInfo['jenis_libur'] ? ' (' . $liburInfo['jenis_libur'] . ')' : ''),
                    'nama_hari' => $namaHari,
                ];
            } elseif ($isWeekend) {
                $result[$d] = [
                    'is_libur' => true,
                    'is_weekend' => true,
                    'tipe' => 'WEEKEND',
                    'keterangan' => $namaHari . ' (Akhir Pekan)',
                    'nama_hari' => $namaHari,
                ];
            } else {
                $result[$d] = [
                    'is_libur' => false,
                    'is_weekend' => false,
                    'tipe' => null,
                    'keterangan' => null,
                    'nama_hari' => $namaHari,
                ];
            }
        }

        return $result;
    }
}
