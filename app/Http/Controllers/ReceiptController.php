<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ReceiptController extends Controller
{
    public function scan(Request $request): JsonResponse
    {
        $request->validate(['image' => 'required|string']);

        $image = $request->input('image');

        // Strip data URL prefix (e.g. "data:image/jpeg;base64,...")
        $mediaType = 'image/jpeg';
        if (str_contains($image, ',')) {
            [$meta, $base64] = explode(',', $image, 2);
            if (str_contains($meta, 'png')) {
                $mediaType = 'image/png';
            } elseif (str_contains($meta, 'webp')) {
                $mediaType = 'image/webp';
            }
        } else {
            $base64 = $image;
        }

        $apiKey = config('services.anthropic.key');
        if (empty($apiKey)) {
            return response()->json(
                ['error' => 'Receipt scanning is not configured. Please set ANTHROPIC_API_KEY in your .env file.'],
                503
            );
        }

        $today = now()->toDateString();

        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-opus-4-8',
            'max_tokens' => 512,
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'   => 'image',
                            'source' => [
                                'type'       => 'base64',
                                'media_type' => $mediaType,
                                'data'       => $base64,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => "Extract transaction details from this receipt image. Return ONLY a valid JSON object with these exact fields:\n- \"description\": merchant/store name (string)\n- \"amount\": total amount as a plain number, no currency symbol (number)\n- \"transaction_date\": date in YYYY-MM-DD format, use $today if not visible (string)\n- \"type\": always \"expense\" for receipts (string)\n- \"notes\": brief summary of items or reference number, max 150 chars, empty string if none (string)\n\nReturn only the JSON object, no markdown fences, no explanation.",
                        ],
                    ],
                ],
            ],
        ]);

        if (!$response->successful()) {
            return response()->json(['error' => 'Failed to analyze receipt. Please try again.'], 500);
        }

        $text = trim($response->json('content.0.text', ''));

        // Strip markdown code fences if model wraps output
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/\s*```$/m', '', $text);
        $text = trim($text);

        $data = json_decode($text, true);

        if (!is_array($data)) {
            if (preg_match('/\{.*\}/s', $text, $m)) {
                $data = json_decode($m[0], true);
            }
        }

        if (!is_array($data) || empty($data['description'])) {
            return response()->json(
                ['error' => 'Could not extract receipt details. Please try a clearer photo.'],
                422
            );
        }

        return response()->json([
            'description'      => (string) ($data['description']      ?? ''),
            'amount'           => (float)  ($data['amount']            ?? 0),
            'transaction_date' => (string) ($data['transaction_date']  ?? $today),
            'type'             => 'expense',
            'notes'            => (string) ($data['notes']             ?? ''),
        ]);
    }
}
