<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DatabaseController extends Controller
{
    private const FORBIDDEN = [
        'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER',
        'TRUNCATE', 'RENAME', 'REPLACE', 'EXEC', 'EXECUTE',
        'GRANT', 'REVOKE', 'LOAD', 'OUTFILE', 'DUMPFILE', 'INTO',
    ];

    public function index(): Response
    {
        $tables = Schema::getTableListing();
        sort($tables);

        return Inertia::render('Admin/Database', [
            'tables' => $tables,
        ]);
    }

    public function query(Request $request): JsonResponse
    {
        $request->validate(['sql' => 'required|string|max:10000']);

        $sql = trim($request->input('sql'));

        if (!preg_match('/^SELECT\s/i', $sql)) {
            return response()->json(['error' => 'Only SELECT statements are allowed.'], 403);
        }

        foreach (self::FORBIDDEN as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $sql)) {
                return response()->json(['error' => "Keyword '{$keyword}' is not permitted."], 403);
            }
        }

        // Auto-add LIMIT 500 when none specified
        if (!preg_match('/\bLIMIT\s+\d+/i', $sql)) {
            $sql .= ' LIMIT 500';
        }

        $start = microtime(true);

        try {
            $raw = DB::select($sql);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $ms   = round((microtime(true) - $start) * 1000, 2);
        $rows = array_map(fn ($r) => (array) $r, $raw);

        return response()->json([
            'columns' => $rows ? array_keys($rows[0]) : [],
            'rows'    => $rows,
            'count'   => count($rows),
            'time_ms' => $ms,
            'sql'     => $sql,
        ]);
    }
}
