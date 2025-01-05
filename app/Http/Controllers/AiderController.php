<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AiderController extends Controller
{
    /**
     * Handle a request to interact with Aider.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function interact(Request $request)
    {
        $data = $request->all();

        // Send data to Aider API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.aider.api_key'),
            'Accept' => 'application/json',
        ])->post(config('services.aider.endpoint') . '/interact', [
            'data' => $data,
        ]);

        if ($response->failed()) {
            Log::error('AiderController: Aider API request failed.', ['response' => $response->body()]);
            return response()->json(['error' => 'Aider API request failed.'], 500);
        }

        $result = $response->json();

        return response()->json(['result' => $result]);
    }
}
