<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\AI\AiderService;

class AiderController extends Controller
{
    public function __construct(protected AiderService $aiderService)
    {
        //
    }
    public function interact(Request $request)
    {
        $data = $request->all();

        try {
            $result = $this->aiderService->interact($data);
        } catch (\Exception $e) {
            Log::error('AiderController: Interaction with Aider failed.', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Aider interaction failed.'], 500);
        }

        return response()->json(['result' => $result]);
    }
}
