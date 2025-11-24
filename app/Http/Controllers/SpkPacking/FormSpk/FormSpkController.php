<?php

namespace App\Http\Controllers\SpkPacking\FormSpk;

use App\Http\Controllers\Controller;
use App\Models\LevelStok;
use App\Models\MasterItem;
use App\Models\MonitoringFGHeader;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\SpkPackingHeader;
use App\Models\SpkPackingDetail;
use Illuminate\Support\Facades\DB;

class FormSpkController extends Controller
{
    public function index()
    {
        return view('pages.spkpacking.formspk.index');
    }

    public function getMasterItems(Request $request)
    {
        $search = $request->get('search', '');

        $items = MasterItem::when($search, function ($query, $search) {
            $query->where('part_number', 'like', "%$search%");
        })->limit(20)->get();

        $results = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->part_number,
                'customer' => $item->customer,
                'nama_part' => $item->nama_part,
            ];
        });

        return response()->json(['results' => $results]);
    }

    public function getItemInfo(Request $request)
    {
        $itemId = $request->input('item_id');
        $bulan = $request->input('bulan', now()->month);
        $tahun = $request->input('tahun', now()->year);

        $item = MasterItem::find($itemId);

        if (!$item) {
            return response()->json(['error' => 'Item not found'], 404);
        }

        // Cek Qty/Set Box dan Level Stock
        $level = LevelStok::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->with(['details' => function ($query) use ($item) {
                $query->where('customer', $item->customer)
                      ->where('part_number', $item->part_number);
            }])
            ->first();

        $detail = $level?->details->first();
        $qty_set_box = $detail?->qty_set_box ?? 0;
        $level_stock = $detail?->safety_fg ?? 0;

        $fgHeader = MonitoringFGHeader::where([
            ['customer', $item->customer],
            ['part_number', $item->part_number],
            ['bulan', $bulan],
            ['tahun', $tahun],
        ])->with('details')->first();

        $lastBalance = $fgHeader?->details->sortByDesc('tanggal')->first()?->balance_n ?? 0;

        return response()->json([
            'customer' => $item->customer,
            'nama_models' => $item->nama_part,
            'qty_set_box' => $qty_set_box,
            'level_stock' => $level_stock,
            'stock_fg' => $lastBalance,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'details' => 'required|array|min:1',
            'details.*.part_number' => 'required|string',
            'details.*.customer' => 'nullable|string',
            'details.*.nama_models' => 'nullable|string',
            'details.*.qty_set_box' => 'nullable|numeric',
            'details.*.level_stock' => 'nullable|numeric',
            'details.*.stock_fg' => 'nullable|numeric',
            'details.*.wip' => 'nullable|numeric',
            'details.*.qty_spk_set' => 'nullable|numeric',
            'details.*.refer_kanban' => 'nullable|string',
            'details.*.keterangan' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $now = now();

            $header = SpkPackingHeader::create([
                'tanggal' => (int) $now->format('d'),
                'bulan' => $now->month,
                'tahun' => $now->year,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['details'] as $item) {
                $detail = new SpkPackingDetail([
                    'customer' => $item['customer'] ?? null,
                    'part_number' => $item['part_number'],
                    'nama_models' => $item['nama_models'] ?? null,
                    'qty_per_set_box' => $item['qty_set_box'] ?? 0,
                    'level_stock' => $item['level_stock'] ?? 0,
                    'stock_fg' => $item['stock_fg'] ?? 0,
                    'wip' => $item['wip'] ?? 0,
                    'qty_spk_set' => $item['qty_spk_set'] ?? 0,
                    'refer_kanban_po' => $item['refer_kanban'] ?? null,
                    'keterangan' => $item['keterangan'] ?? null,
                ]);

                $header->details()->save($detail);
            }

            DB::commit();

            return response()->json([
                'message' => '✅ Data SPK berhasil disimpan.',
                'header_id' => $header->id
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => '❌ Gagal menyimpan data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
