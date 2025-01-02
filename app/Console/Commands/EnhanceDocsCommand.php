<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ParsedItem;
use App\Services\AI\DocEnhancer;

class EnhanceDocsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'doc:enhance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enhances documentation for parsed items using an AI service';

    /**
     * @var DocEnhancer
     */
    protected DocEnhancer $docEnhancer;

    /**
     * Create a new command instance.
     *
     * @param DocEnhancer $docEnhancer
     */
    public function __construct(DocEnhancer $docEnhancer)
    {
        parent::__construct();
        $this->docEnhancer = $docEnhancer;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $items = ParsedItem::where(function($query) {
            $query->whereNull('details->description')
                  ->orWhere('details->description', '');
        })->get();

        if ($items->isEmpty()) {
            $this->info('No parsed items require documentation enhancement.');
            return 0;
        }

        $items->chunk(10)->each(function ($chunk) {
            foreach ($chunk as $item) {
                $this->info("Enhancing documentation for: {$item->type} {$item->name}");

                $enhancedDescription = $this->docEnhancer->enhanceDescription($item);

                if ($enhancedDescription) {
                    $item->details = array_merge($item->details ?? [], [
                        'description' => $enhancedDescription
                    ]);
                    $item->save();

                    $this->info("Updated description for {$item->name}.");
                } else {
                    $this->warn("Failed to enhance description for {$item->name}.");
                }
            }
        });

        $bar->finish();

        $this->info('Documentation enhancement process completed.');
        return 0;
    }
}
