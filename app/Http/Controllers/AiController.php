<?php

namespace App\Http\Controllers;

use App\DataObjects\TicketData;
use App\Services\GroqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Pest\Support\Str;

class AiController extends Controller
{
    public function __invoke(Request $request, GroqService $groqService): JsonResponse
    {
        $validated = $request->validate([
            'img' => 'required|image|max:2048|mimes:jpeg,png,jpg',
        ]);

        $img = $validated['img'];
        $imageName = Str::random(20).'.'.$img->extension();

        Storage::put("private/{$imageName}", file_get_contents($img->getRealPath()));

        $imageUrl = Storage::temporaryUrl("private/{$imageName}", now()->addMinutes(5));

        $response = $groqService->parseTicket($imageUrl);

        $ticket = TicketData::fromResponse($response);

        return response()->json([
            'response' => $ticket->toArray(),
        ]);
    }
}
