<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ParsedItem;
use Illuminate\Support\Facades\File;
use App\Services\AI\EndpointDocGenerator;

/**
 * Generates Markdown documentation for each endpoint.
 */
class GenerateDocsCommand extends Command
{
    protected $signature = 'doc:generate {--limit=} {--store}';

    protected $description = 'Generates Markdown documentation for each endpoint';

    protected EndpointDocGenerator $docGenerator;

    public function __construct(EndpointDocGenerator $docGenerator)
    {
        parent::__construct();
        $this->docGenerator = $docGenerator;
    }

    public function handle()
        $limit = $this->option('limit');

        $parsedItems = ParsedItem::where('type', 'Method');

        if ($limit) {
            $parsedItems = $parsedItems->limit($limit);
        }

        $parsedItems = $parsedItems->get();

        $groupedItems = $parsedItems->groupBy(function ($item) {
            return $item->details['namespace'] ?? 'global';
        });

        foreach ($groupedItems as $groupName => $items) {
            $markdownContent = "# {$groupName} Endpoints\n\n";
            
            foreach ($items as $item) {
                $className   = $item->details['class_name'];
                $methodName  = $item->name;
                $restlerTags = $item->annotations;
                $parameters  = $item->details['params'];

                // Generate AI summary
                $summary = $this->docGenerator->generateSummary($item);

                $markdownContent .= "## {$className}::{$methodName}\n\n";

                $markdownContent .= "### Endpoint Summary\n\n";
                if ($summary) {
                    $markdownContent .= "{$summary}\n\n";
                } else {
                    $markdownContent .= "No summary available.\n\n";
                }

                $markdownContent .= "### Endpoint Details\n\n";
                foreach ($restlerTags as $tag => $value) {
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    $markdownContent .= "- **{$tag}**: {$value}\n";
                }
                $markdownContent .= "\n";

                $markdownContent .= "### Parameters\n\n";
                if (!empty($parameters)) {
                    foreach ($parameters as $param) {
                        $paramName = $param['name'];
                        $paramType = $param['type'];
                        $markdownContent .= "- `{$paramName}` ({$paramType})\n";
                    }
                } else {
                    $markdownContent .= "This endpoint does not accept any parameters.\n";
                }
                $markdownContent .= "\n";
            }

            // Optionally save to file or output to CLI
            if ($this->option('store')) {
                // Generate file path based on group name
                $filePath = "docs/endpoints/{$groupName}.md";

                $directory = dirname($filePath);
                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }

                File::put($filePath, $markdownContent);
                $this->info("Documentation for {$groupName} saved to {$filePath}.");
            } else {
                $this->line($markdownContent);
            }
        }
            $markdownContent = "# {$groupName} Endpoints\n\n";
            
            foreach ($items as $item) {
                $className = $item->details['class_name'];
                $restlerTags = $item->annotations;
                $parameters = $item->details['params'];

                $markdownContent .= "## {$className}\n\n";
                $markdownContent .= "### Endpoint Details\n\n";
                foreach ($restlerTags as $tag => $value) {
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    $markdownContent .= "- **{$tag}**: {$value}\n";
                }
                $markdownContent .= "\n";

                $markdownContent .= "### Parameters\n\n";
                foreach ($parameters as $param) {
                    $paramName = $param['name'];
                    $paramType = $param['type'];
                    $markdownContent .= "- `{$paramName}` ({$paramType})\n";
                }
                $markdownContent .= "\n";
            }

            $url = $restlerTags['url'] ?? 'unknown';
            $httpMethod = strtolower(strtok($url, ' '));
            $name = $item->name;

            $filePath = "docs/endpoints/{$url}/{$httpMethod}_{$name}.md";

            $directory = dirname($filePath);
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            File::put($filePath, $markdownContent);
        }

        $this->info('Documentation generated successfully.');
    }
}
