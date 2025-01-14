# Implementation Plan: Migrating `config/ai.php` to Laravel Models

## Overview

This document outlines a streamlined process to migrate the existing `config/ai.php` configuration file to a database-driven approach using Laravel models. This migration enhances flexibility, scalability, and maintainability by enabling dynamic management of AI configurations through the application's interface.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Step 1: Analyze Current Configuration](#step-1-analyze-current-configuration)
3. [Step 2: Design the Database Schema](#step-2-design-the-database-schema)
4. [Step 3: Create Models and Migrations](#step-3-create-models-and-migrations)
5. [Step 4: Define Model Relationships](#step-4-define-model-relationships)
6. [Step 5: Seed the Database](#step-5-seed-the-database)
7. [Step 6: Refactor Application Logic](#step-6-refactor-application-logic)
8. [Step 7: Implement Configuration Service](#step-7-implement-configuration-service)
9. [Step 8: Optimize with Caching](#step-8-optimize-with-caching)
10. [Step 9: Testing](#step-9-testing)
11. [Step 10: Update Documentation and Admin Interface](#step-10-update-documentation-and-admin-interface)
12. [Step 11: Rollback Strategy](#step-11-rollback-strategy)
13. [Step 12: Future Enhancements](#step-12-future-enhancements)

---

## Prerequisites

- **Laravel Version:** Ensure you are using Laravel 11+.
- **PHP Version:** PHP 8.4+ with `strict_types=1`.
- **Tools:** Composer, Artisan CLI, a database system (e.g., MySQL, PostgreSQL).
- **Backup:** Backup your current database and configuration files before proceeding.

---

## Step 1: Analyze Current Configuration

### Objectives

- **Understand** the structure and content of `config/ai.php`.
- **Identify** the different configuration sections and their relationships.

### Actions

1. **Review `config/ai.php`:**
   - **Sections Identified:**
     - OpenAI API Configuration
     - Default Model Configuration
     - OpenAI Models Configuration
     - Static Analysis Tools Configuration
     - AI Passes Configuration
     - Multi-Pass Analysis Order

2. **Document Relationships:**
   - Determine how different sections interrelate, such as which models are used by which passes.

---

## Step 2: Design the Database Schema

### Objectives

- **Translate** the static configuration into a relational database schema.
- **Ensure** the schema accommodates all configuration aspects.

### Proposed Entities and Relationships

1. **AIConfiguration**
   - **Fields:**
     - `id` (Primary Key)
     - `openai_api_key` (String)
     - `created_at` & `updated_at` (Timestamps)
   - **Relationships:**
     - Has many **AIModels**
     - Has many **StaticAnalysisTools**
     - Has many **AIPasses**
     - Has many **PassOrders**

2. **AIModel**
   - **Fields:**
     - `id`
     - `ai_configuration_id` (Foreign Key)
     - `model_name` (String)
     - `max_tokens` (Integer, Nullable)
     - `temperature` (Float, Nullable)
     - `supports_system_message` (Boolean)
     - `token_limit_parameter` (String, Nullable)
     - Timestamps
   - **Relationships:**
     - Belongs to **AIConfiguration**
     - Has many **AIPasses**

3. **StaticAnalysisTool**
   - **Fields:**
     - `id`
     - `ai_configuration_id` (Foreign Key)
     - `name` (String)
     - `enabled` (Boolean)
     - `command` (String)
     - `options` (JSON)
     - `output_format` (String)
     - Timestamps
   - **Relationships:**
     - Belongs to **AIConfiguration**

4. **AIPass**
   - **Fields:**
     - `id`
     - `ai_configuration_id` (Foreign Key)
     - `name` (String)
     - `operation_identifier` (String)
     - `model_id` (Foreign Key to **AIModel**, Nullable)
     - `max_tokens` (Integer, Nullable)
     - `temperature` (Float, Nullable)
     - `type` (String)
     - `system_message` (Text, Nullable)
     - `prompt_sections` (JSON)
     - Timestamps
   - **Relationships:**
     - Belongs to **AIConfiguration**
     - Belongs to **AIModel**
     - Has many **PassOrders**

5. **PassOrder**
   - **Fields:**
     - `id`
     - `ai_configuration_id` (Foreign Key)
     - `ai_pass_id` (Foreign Key to **AIPass**)
     - `order` (Integer)
     - Timestamps
   - **Relationships:**
     - Belongs to **AIConfiguration**
     - Belongs to **AIPass**

### Diagram

*Consider creating an ER diagram to visualize the schema.*

---

## Step 3: Create Laravel Models and Migrations

### Objectives

- **Generate** models and corresponding migration files.
- **Define** the database structure as per the schema design.

### Actions

1. **Generate Models with Migrations:**

   Execute the following Artisan commands:

   ```bash
   php artisan make:model AIConfiguration -m
   php artisan make:model AIModel -m
   php artisan make:model StaticAnalysisTool -m
   php artisan make:model AIPass -m
   php artisan make:model PassOrder -m
   ```

2. **Define Migration Files:**

   Populate each migration file located in `database/migrations/` with the appropriate schema.

   - **`create_ai_configurations_table.php`:**

     ```php
     public function up()
     {
         Schema::create('ai_configurations', function (Blueprint $table) {
             $table->id();
             $table->string('openai_api_key')->default('');
             $table->timestamps();
         });
     }
     ```

   - **`create_ai_models_table.php`:**

     ```php
     public function up()
     {
         Schema::create('ai_models', function (Blueprint $table) {
             $table->id();
             $table->foreignId('ai_configuration_id')->constrained()->onDelete('cascade');
             $table->string('model_name');
             $table->integer('max_tokens')->nullable();
             $table->float('temperature')->nullable();
             $table->boolean('supports_system_message')->default(false);
             $table->string('token_limit_parameter')->nullable();
             $table->timestamps();
         });
     }
     ```

   - **`create_static_analysis_tools_table.php`:**

     ```php
     public function up()
     {
         Schema::create('static_analysis_tools', function (Blueprint $table) {
             $table->id();
             $table->foreignId('ai_configuration_id')->constrained()->onDelete('cascade');
             $table->string('name');
             $table->boolean('enabled')->default(true);
             $table->string('command');
             $table->json('options');
             $table->string('output_format');
             $table->timestamps();
         });
     }
     ```

   - **`create_ai_passes_table.php`:**

     ```php
     public function up()
     {
         Schema::create('ai_passes', function (Blueprint $table) {
             $table->id();
             $table->foreignId('ai_configuration_id')->constrained()->onDelete('cascade');
             $table->string('name');
             $table->string('operation_identifier');
             $table->foreignId('model_id')->nullable()->constrained('ai_models')->onDelete('set null');
             $table->integer('max_tokens')->nullable();
             $table->float('temperature')->nullable();
             $table->string('type');
             $table->text('system_message')->nullable();
             $table->json('prompt_sections')->nullable();
             $table->timestamps();
         });
     }
     ```

   - **`create_pass_orders_table.php`:**

     ```php
     public function up()
     {
         Schema::create('pass_orders', function (Blueprint $table) {
             $table->id();
             $table->foreignId('ai_configuration_id')->constrained()->onDelete('cascade');
             $table->foreignId('ai_pass_id')->constrained()->onDelete('cascade');
             $table->integer('order');
             $table->timestamps();
         });
     }
     ```

3. **Run Migrations:**

   Execute the migrations to create the tables in the database.

   ```bash
   php artisan migrate
   ```

---

## Step 4: Define Model Relationships

### Objectives

- **Establish** relationships between models to reflect the database schema.
- **Ensure** Eloquent ORM can efficiently handle related data.

### Actions

1. **`AIConfiguration.php`:**

   ```php
   <?php

   namespace App\Models;

   use Illuminate\Database\Eloquent\Factories\HasFactory;
   use Illuminate\Database\Eloquent\Model;

   class AIConfiguration extends Model
   {
       use HasFactory;

       protected $fillable = ['openai_api_key'];

       public function models()
       {
           return $this->hasMany(AIModel::class);
       }

       public function staticAnalysisTools()
       {
           return $this->hasMany(StaticAnalysisTool::class);
       }

       public function aiPasses()
       {
           return $this->hasMany(AIPass::class);
       }

       public function passOrders()
       {
           return $this->hasMany(PassOrder::class);
       }
   }
   ```

2. **`AIModel.php`:**

   ```php
   <?php

   namespace App\Models;

   use Illuminate\Database\Eloquent\Factories\HasFactory;
   use Illuminate\Database\Eloquent\Model;

   class AIModel extends Model
   {
       use HasFactory;

       protected $fillable = [
           'ai_configuration_id',
           'model_name',
           'max_tokens',
           'temperature',
           'supports_system_message',
           'token_limit_parameter',
       ];

       public function aiConfiguration()
       {
           return $this->belongsTo(AIConfiguration::class);
       }

       public function aiPasses()
       {
           return $this->hasMany(AIPass::class, 'model_id');
       }
   }
   ```

3. **`StaticAnalysisTool.php`:**

   ```php
   <?php

   namespace App\Models;

   use Illuminate\Database\Eloquent\Factories\HasFactory;
   use Illuminate\Database\Eloquent\Model;

   class StaticAnalysisTool extends Model
   {
       use HasFactory;

       protected $fillable = [
           'ai_configuration_id',
           'name',
           'enabled',
           'command',
           'options',
           'output_format',
       ];

       protected $casts = [
           'options' => 'array',
       ];

       public function aiConfiguration()
       {
           return $this->belongsTo(AIConfiguration::class);
       }
   }
   ```

4. **`AIPass.php`:**

   ```php
   <?php

   namespace App\Models;

   use Illuminate\Database\Eloquent\Factories\HasFactory;
   use Illuminate\Database\Eloquent\Model;

   class AIPass extends Model
   {
       use HasFactory;

       protected $fillable = [
           'ai_configuration_id',
           'name',
           'operation_identifier',
           'model_id',
           'max_tokens',
           'temperature',
           'type',
           'system_message',
           'prompt_sections',
       ];

       protected $casts = [
           'prompt_sections' => 'array',
       ];

       public function aiConfiguration()
       {
           return $this->belongsTo(AIConfiguration::class);
       }

       public function model()
       {
           return $this->belongsTo(AIModel::class, 'model_id');
       }

       public function passOrders()
       {
           return $this->hasMany(PassOrder::class);
       }
   }
   ```

5. **`PassOrder.php`:**

   ```php
   <?php

   namespace App\Models;

   use Illuminate\Database\Eloquent\Factories\HasFactory;
   use Illuminate\Database\Eloquent\Model;

   class PassOrder extends Model
   {
       use HasFactory;

       protected $fillable = [
           'ai_configuration_id',
           'ai_pass_id',
           'order',
       ];

       public function aiConfiguration()
       {
           return $this->belongsTo(AIConfiguration::class);
       }

       public function aiPass()
       {
           return $this->belongsTo(AIPass::class);
       }
   }
   ```

---

## Step 5: Seed the Database

**Objective:** Populate the database with existing configurations from `config/ai.php`.

**Actions:**

1. **Create Seeder:**

   ```bash
   php artisan make:seeder AIConfigurationSeeder
   ```

2. **Define Seeder Logic:**

   **`database/seeders/AIConfigurationSeeder.php`**

   ```php
   namespace Database\Seeders;

   use Illuminate\Database\Seeder;
   use App\Models\AIConfiguration;
   use App\Models\AIModel;
   use App\Models\StaticAnalysisTool;
   use App\Models\AIPass;
   use App\Models\PassOrder;

   class AIConfigurationSeeder extends Seeder
   {
       public function run()
       {
           $config = config('ai');

           // Create AI Configuration
           $aiConfig = AIConfiguration::create([
               'openai_api_key' => $config['openai_api_key'],
           ]);

           // Seed AI Models
           foreach ($config['models'] as $modelKey => $modelData) {
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
                   'enabled' => $toolData['enabled'],
                   'command' => $toolData['command'],
                   'options' => $toolData['options'],
                   'output_format' => $toolData['output_format'],
               ]);
           }

           // Seed AI Passes
           foreach ($config['passes'] as $passKey => $passData) {
               $modelId = $passData['model'] 
                   ? AIModel::where('model_name', $passData['model'])->value('id') 
                   : null;

               $aiPass = AIPass::create([
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
   ```

3. **Register Seeder:**

   **`database/seeders/DatabaseSeeder.php`**

   ```php
   namespace Database\Seeders;

   use Illuminate\Database\Seeder;

   class DatabaseSeeder extends Seeder
   {
       public function run()
       {
           $this->call([
               AIConfigurationSeeder::class,
           ]);
       }
   }
   ```

4. **Execute Seeder:**

   ```bash
   php artisan migrate --fresh
   php artisan db:seed --class=AIConfigurationSeeder
   ```

   > **Note:** The `--fresh` flag drops all tables before migrating. Use cautiously outside development environments.

---

## Step 6: Refactor Services and Controllers

### Objectives

- **Update** application logic to retrieve configurations from the database instead of the config file.
- **Ensure** seamless integration with the new database-driven configurations.

### Actions

1. **Identify Dependencies:**

   Locate all services, controllers, and other classes that utilize `config('ai')`.

2. **Modify `CodeAnalysisService`:**

   **Before:**

   ```php
   public function __construct(
       protected OpenAIService $openAIService,
       protected ParserService $parserService
   ) {
       $this->apiKey = config('ai.openai_api_key');
       // ...
   }
   ```

   **After:**

   ```php
   use App\Models\AIConfiguration;

   public function __construct(
       protected OpenAIService $openAIService,
       protected ParserService $parserService
   ) {
       $aiConfig = AIConfiguration::latest()->first();
       $this->apiKey = $aiConfig->openai_api_key;
       // ...
   }
   ```

3. **Update AI Pass Retrieval:**

   **Before:**

   ```php
   $passes = config('ai.ai.passes.pass_order.pass_order');

   foreach ($passes as $passName) {
       // Perform pass operations
   }
   ```

   **After:**

   ```php
   $aiConfig = AIConfiguration::latest()->first();
   $passOrders = $aiConfig->passOrders()->with('aiPass')->orderBy('order')->get();

   foreach ($passOrders as $passOrder) {
       $pass = $passOrder->aiPass;
       // Perform pass operations using $pass
   }
   ```

4. **Update Static Analysis Tools Usage:**

   Replace instances where static analysis tools are retrieved from the config with database queries.

   **Example:**

   ```php
   // Before
   $tools = config('ai.static_analysis_tools');

   // After
   $aiConfig = AIConfiguration::latest()->first();
   $tools = $aiConfig->staticAnalysisTools()->where('enabled', true)->get();
   ```

5. **Refactor Any Other Relevant Classes:**

   Ensure all classes accessing AI configurations are updated to use the new models.

---

## Step 7: Update Configuration Access Points

### Objectives

- **Centralize** the retrieval of AI configurations.
- **Improve** maintainability by abstracting configuration access.

### Actions

1. **Create a Configuration Service:**

   Generate a service to handle AI configurations.

   ```bash
   php artisan make:service AIConfigurationService
   ```

2. **Define `AIConfigurationService`:**

   **`app/Services/AIConfigurationService.php`:**

   ```php
   <?php

   namespace App\Services;

   use App\Models\AIConfiguration;
   use Illuminate\Support\Facades\Cache;

   class AIConfigurationService
   {
       protected AIConfiguration $aiConfig;

       public function __construct()
       {
           $this->aiConfig = Cache::remember('ai_configuration', 3600, function () {
               return AIConfiguration::latest()->first();
           });
       }

       public function getAPIKey(): string
       {
           return $this->aiConfig->openai_api_key;
       }

       public function getModels()
       {
           return $this->aiConfig->models;
       }

       public function getStaticAnalysisTools()
       {
           return $this->aiConfig->staticAnalysisTools()->where('enabled', true)->get();
       }

       public function getPassesOrdered()
       {
           return $this->aiConfig->passOrders()->with('aiPass')->orderBy('order')->get();
       }

       public function refreshCache()
       {
           Cache::forget('ai_configuration');
           $this->aiConfig = AIConfiguration::latest()->first();
           Cache::remember('ai_configuration', 3600, fn() => $this->aiConfig);
       }
   }
   ```

3. **Inject `AIConfigurationService` Where Needed:**

   Update constructors to inject `AIConfigurationService` instead of accessing the config directly.

   **Example:**

   ```php
   use App\Services\AIConfigurationService;

   public function __construct(
       protected AIConfigurationService $aiConfigService,
       protected OpenAIService $openAIService,
       protected ParserService $parserService
   ) {
       $this->apiKey = $aiConfigService->getAPIKey();
       // ...
   }
   ```

---

## Step 8: Implement Caching (Optional)

### Objectives

- **Enhance** performance by reducing database queries for configuration data.
- **Ensure** cache consistency when configurations are updated.

### Actions

1. **Cache Configuration Data:**

   Implement caching within the `AIConfigurationService` as shown in Step 7.

2. **Handle Cache Invalidation:**

   Ensure that any updates to AI configurations refresh the cache.

   **Example:**

   ```php
   public function updateAIConfiguration(array $data)
   {
       $aiConfig = AIConfiguration::latest()->first();
       $aiConfig->update($data);
       $this->refreshCache();
   }
   ```

3. **Use Cache Facade Appropriately:**

   Leverage Laravel's caching mechanisms to store frequently accessed configuration data.

---

## Step 9: Testing the Migration

### Objectives

- **Validate** the migration by ensuring all configurations are correctly transferred and accessible.
- **Detect** and resolve any issues arising from the migration.

### Actions

1. **Unit Testing:**

   - **Test Seeder:**
     - Verify that the seeder correctly populates all tables with the expected data.
   
   - **Test Services:**
     - Ensure `AIConfigurationService` retrieves and caches data as intended.
   
   - **Test Models:**
     - Confirm model relationships are correctly defined and functional.

2. **Integration Testing:**

   - **Workflow Testing:**
     - Run through AI analysis workflows to ensure services and controllers interact seamlessly with the new configuration source.
   
   - **Edge Cases:**
     - Test scenarios where configurations might be missing or incomplete.

3. **Manual Testing:**

   - **Admin Interface (if implemented):**
     - Manually verify that configurations can be viewed and edited.
   
   - **AI Operations:**
     - Perform actual AI analysis to ensure configurations are correctly applied.

4. **Automated Testing:**

   - **Update Existing Tests:**
     - Modify existing PHPUnit tests to accommodate the new configuration retrieval method.
   
   - **Create New Tests:**
     - Develop tests specifically targeting the new models and services.

   > **Example:** Testing `AIConfigurationService`

   ```php
   public function test_ai_configuration_service_retrieves_correct_api_key()
   {
       $service = new AIConfigurationService();
       $this->assertEquals(config('ai.openai_api_key'), $service->getAPIKey());
   }
   ```

---

## Step 10: Update Documentation and Admin Interface

### Objectives

- **Ensure** all project documentation reflects the migration.
- **Provide** an interface for managing AI configurations dynamically.

### Actions

1. **Update Project Documentation:**

   - **Remove References** to `config/ai.php`.
   - **Add Instructions** on managing AI configurations via the database.
   - **Detail** the new models and their relationships.

2. **Create an Admin Interface (Optional but Recommended):**

   - **Generate Controllers and Views:**
     - Create controllers to handle CRUD operations for AI configurations, models, tools, passes, and pass orders.
   
   - **Implement Routes:**
     - Define routes in `routes/web.php` for managing AI configurations.
   
   - **Secure the Interface:**
     - Restrict access to authorized users only.

   > **Example Routes:**

   ```php
   use App\Http\Controllers\Admin\AIConfigurationController;

   Route::prefix('admin')->middleware(['auth', 'can:manage-ai-configurations'])->group(function () {
       Route::resource('ai-configurations', AIConfigurationController::class);
   });
   ```

3. **Document the Migration:**

   - **Create a Migration Guide:**
     - Provide a clear guide on how the migration was performed, including steps to revert if necessary.

   - **Update `README.md`:**
     - Include information about the new configuration management approach.

---

## Step 11: Rollback Plan

### Objectives

- **Prepare** for potential issues by having a strategy to revert changes.
- **Ensure** data integrity during rollback.

### Actions

1. **Backup Database:**

   - Before starting the migration, ensure you have a complete backup of your database.
   
   ```bash
   mysqldump -u username -p database_name > backup.sql
   ```

2. **Version Control:**

   - Commit all changes incrementally. Use descriptive commit messages for each step.
   
   ```bash
   git add .
   git commit -m "Initial commit for AI configuration migration: schema design"
   ```

3. **Revert Migrations:**

   - If issues arise, you can rollback the last batch of migrations.
   
   ```bash
   php artisan migrate:rollback
   ```

4. **Restore from Backup:**

   - In case of severe issues, restore the database from the backup.
   
   ```bash
   mysql -u username -p database_name < backup.sql
   ```

5. **Revert Code Changes:**

   - Use Git to checkout the previous stable commit.
   
   ```bash
   git checkout HEAD~1
   ```

---

## Step 12: Next Steps and Enhancements

### Objectives

- **Enhance** the new configuration management system for better functionality.
- **Implement** best practices to maintain and scale the system.

### Actions

1. **Dynamic Configuration Updates:**

   - Allow administrators to update AI configurations without deploying code changes via the admin interface.

2. **Versioning Configurations:**

   - Implement version control for AI configurations to track changes over time and enable rollback to previous versions.

3. **Enhanced Validation:**

   - Add validation rules in models or request classes to ensure data integrity when configurations are created or updated.

   > **Example: Adding Validation in Controller:**

   ```php
   public function store(Request $request)
   {
       $validated = $request->validate([
           'model_name' => 'required|string|unique:ai_models,model_name',
           'max_tokens' => 'nullable|integer|min:1',
           'temperature' => 'nullable|numeric|between:0,1',
           // Add other fields as necessary
       ]);

       AIModel::create($validated);

       // ...
   }
   ```

4. **Implement Auditing:**

   - Track changes to AI configurations using packages like [Laravel Auditing](https://github.com/owen-it/laravel-auditing) to monitor who changed what and when.

5. **Automate Deployment:**

   - Integrate the migration and seeder into your deployment pipeline to ensure consistency across environments.

6. **Monitor Performance:**

   - Use monitoring tools to track the performance impact of the new configuration system and optimize as necessary.

7. **Security Enhancements:**

   - Ensure that sensitive data, such as `openai_api_key`, is stored securely. Consider encrypting such fields in the database.

   > **Example: Encrypting in Model:**

   ```php
   protected $casts = [
       'openai_api_key' => 'encrypted',
       // Other casts
   ];
   ```

8. **Feedback Loop:**

   - Gather feedback from users and developers to identify areas of improvement and iterate on the configuration system accordingly.

---

## Conclusion

Migrating `config/ai.php` to a database-driven configuration system involves careful planning and execution. By following this implementation plan, you can achieve a more flexible and maintainable AI configuration setup within your Laravel application. Ensure thorough testing and documentation to support ongoing development and future enhancements.

---
