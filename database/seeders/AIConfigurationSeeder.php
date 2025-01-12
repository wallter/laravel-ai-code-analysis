<?php

namespace Database\Seeders;

use App\Models\AIConfiguration;
use App\Models\AIModel;
use App\Models\AIPass;
use App\Models\PassOrder;
use App\Models\StaticAnalysisTool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class AIConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Retrieve the AI configuration from config/ai.php
        $config = Config::get('ai');

        // Create AIConfiguration without 'openai_api_key'
        $aiConfig = AIConfiguration::create();

        // Seed AI Models
        foreach ($config['models'] as $modelData) {
            AIModel::create([
                'ai_configuration_id' => $aiConfig->id,
                'model_name' => $modelData['model_name'],
                'max_tokens' => $modelData['max_tokens'] ?? null,
                'temperature' => $modelData['temperature'] ?? null,
                'supports_system_message' => $modelData['supports_system_message'] ?? false,
                'token_limit_parameter' => $modelData['token_limit_parameter'] ?? null,
            ]);
        }

        // Seed Static Analysis Tools
        foreach ($config['static_analysis_tools'] as $toolName => $toolData) {
            StaticAnalysisTool::create([
                'ai_configuration_id' => $aiConfig->id,
                'name' => $toolName,
                'enabled' => $toolData['enabled'] ?? true,
                'command' => $toolData['command'],
                'options' => $toolData['options'] ?? [],
                'output_format' => $toolData['output_format'] ?? 'json',
            ]);
        }

        // Seed AI Passes
        foreach ($config['passes'] as $passKey => $passData) {
            $modelId = $passData['model']
                ? AIModel::where('model_name', $passData['model'])->value('id')
                : null;

            AIPass::create([
                'ai_configuration_id' => $aiConfig->id,
                'name' => $passKey,
                'operation_identifier' => $passData['operation_identifier'],
                'model_id' => $modelId,
                'max_tokens' => $passData['max_tokens'] ?? null,
                'temperature' => $passData['temperature'] ?? null,
                'type' => $passData['type'],
                'system_message' => $passData['system_message'] ?? null,
                'prompt_sections' => $passData['prompt_sections'] ?? null,
            ]);
        }

        // Seed Pass Orders
        foreach ($config['operations']['multi_pass_analysis']['pass_order'] as $index => $passName) {
            $aiPass = AIPass::where('name', $passName)->first();
            if ($aiPass) {
                PassOrder::create([
                    'ai_configuration_id' => $aiConfig->id,
                    'ai_pass_id' => $aiPass->id,
                    'order' => $index + 1,
                ]);
            }
        }
    }
}
