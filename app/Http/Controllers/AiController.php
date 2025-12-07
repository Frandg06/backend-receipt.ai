<?php

namespace App\Http\Controllers;

use App\Http\Services\PrismService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Prism;

class AiController extends Controller
{
    protected PrismService $prismService;


    public function __construct(PrismService $prismService)
    {
        $this->prismService = $prismService;
    }

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'img' => 'required|image|max:2048|mimes:jpeg,png,jpg',
        ]);

        $imageData = $this->imageToBase64($request);

        $response = $this->prismService->sendImageToAi($imageData);

        return response()->json([
            'response' => $response,
        ]);
    }

    private function imageToBase64(Request $request)
    {
        $imagePath = $request->file('img')->getRealPath();
        $imageData = base64_encode(file_get_contents($imagePath));

        return $imageData;
    }
}
