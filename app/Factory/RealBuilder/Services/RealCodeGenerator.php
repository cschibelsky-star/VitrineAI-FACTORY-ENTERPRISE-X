<?php

declare(strict_types=1);

namespace App\Factory\RealBuilder\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RealCodeGenerator
{
    public function __construct(
        protected RealBuilderNameService $names,
    ) {
    }

    public function generate(string $blueprintSlug): array
    {
        $blueprintPath = storage_path('app/factory/blueprints/' . $blueprintSlug . '.json');

        if (! File::exists($blueprintPath)) {
            throw new \RuntimeException("Blueprint não encontrado: {$blueprintSlug}");
        }

        $blueprint = json_decode((string) File::get($blueprintPath), true);
        $base = storage_path('app/factory/real-builds/' . $blueprintSlug);

        File::ensureDirectoryExists($base);

        $generated = [];

        foreach (($blueprint['modules'] ?? []) as $index => $module) {
            $generated = array_merge($generated, $this->generateModule($base, $module, $index));
        }

        $report = [
            'blueprint' => $blueprintSlug,
            'system' => $blueprint['name'] ?? $blueprintSlug,
            'status' => 'generated',
            'files_count' => count($generated),
            'files' => $generated,
            'generated_at' => now()->toISOString(),
        ];

        $reportPath = $base . '/REAL_BUILD_REPORT.json';
        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $report['path'] = $reportPath;

        return $report;
    }

    protected function generateModule(string $base, array $module, int $index): array
    {
        $slug = $module['slug'];
        $model = $this->names->modelName($slug);
        $resource = $this->names->resourceName($slug);

        $files = [];

        $files[] = $this->write($base . "/app/Models/{$model}.php", $this->modelCode($module));
        $files[] = $this->write($base . "/app/Policies/{$model}Policy.php", $this->policyCode($model));
        $files[] = $this->write($base . "/app/Filament/Resources/{$resource}.php", $this->resourceCode($module));
        $files[] = $this->write($base . "/app/Filament/Resources/{$resource}/Pages/Create{$model}.php", $this->createPageCode($model, $resource));
        $files[] = $this->write($base . "/app/Filament/Resources/{$resource}/Pages/Edit{$model}.php", $this->editPageCode($model, $resource));
        $files[] = $this->write($base . "/app/Filament/Resources/{$resource}/Pages/List" . Str::studly($slug) . ".php", $this->listPageCode($model, $resource, $slug));
        $files[] = $this->write($base . "/app/Filament/Resources/{$resource}/Pages/View{$model}.php", $this->viewPageCode($model, $resource));
        $files[] = $this->write($base . "/database/seeders/{$model}Seeder.php", $this->seederCode($model));
        $files[] = $this->write($base . "/database/migrations/" . $this->migrationTimestamp($index) . "_create_{$slug}_table.php", $this->migrationCode($module));

        return $files;
    }

    protected function migrationTimestamp(int $index): string
    {
        return now()->addSeconds($index)->format('Y_m_d_His');
    }

    protected function write(string $path, string $content): string
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
        return $path;
    }

    protected function modelCode(array $module): string
    {
        $slug = $module['slug'];
        $model = $this->names->modelName($slug);

        $fillable = array_map(fn ($field) => "'" . $field['name'] . "'", $module['fields'] ?? []);
        $fillableCode = implode(",\n        ", $fillable);

        $relations = [];

        foreach (($module['fields'] ?? []) as $field) {
            if (($field['type'] ?? null) === 'foreignId' || str_ends_with($field['name'], '_id')) {
                $rel = $this->names->relationName($field['name']);
                $related = $field['related_model'] ?? $this->names->relatedModelFromField($field['name']);
                $relations[] = <<<PHP

    public function {$rel}()
    {
        return \$this->belongsTo(\\App\\Models\\{$related}::class);
    }
PHP;
            }
        }

        $relationsCode = implode("\n", $relations);

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class {$model} extends Model
{
    use SoftDeletes;

    protected \$fillable = [
        {$fillableCode}
    ];
{$relationsCode}
}

PHP;
    }

    protected function migrationCode(array $module): string
    {
        $slug = $module['slug'];
        $lines = [];

        foreach (($module['fields'] ?? []) as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? 'string';
            $nullable = ($field['nullable'] ?? true) ? '->nullable()' : '';

            if ($type === 'foreignId' || str_ends_with($name, '_id')) {
                $table = $this->names->relatedTable($name);
                $lines[] = "\$table->foreignId('{$name}')->constrained('{$table}')->cascadeOnDelete();";
            } elseif ($type === 'text') {
                $lines[] = "\$table->text('{$name}'){$nullable};";
            } elseif ($type === 'date') {
                $lines[] = "\$table->date('{$name}'){$nullable};";
            } elseif ($type === 'decimal') {
                $lines[] = "\$table->decimal('{$name}', 12, 2){$nullable};";
            } elseif ($type === 'integer') {
                $lines[] = "\$table->integer('{$name}'){$nullable};";
            } else {
                $lines[] = "\$table->string('{$name}'){$nullable};";
            }
        }

        $fields = implode("\n            ", $lines);

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$slug}', function (Blueprint \$table) {
            \$table->id();
            {$fields}
            \$table->timestamps();
            \$table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$slug}');
    }
};

PHP;
    }

    protected function resourceCode(array $module): string
    {
        $slug = $module['slug'];
        $model = $this->names->modelName($slug);
        $resource = $this->names->resourceName($slug);
        $label = $module['label'] ?? Str::headline($slug);

        $formFields = [];
        $tableColumns = [];

        foreach (($module['fields'] ?? []) as $field) {
            $name = $field['name'];
            $required = !($field['nullable'] ?? true) ? '->required()' : '';

            if (($field['type'] ?? null) === 'foreignId' || str_ends_with($name, '_id')) {
                $rel = $this->names->relationName($name);
                $formFields[] = "Forms\\Components\\Select::make('{$name}')->relationship('{$rel}', 'nome')->searchable()->preload(){$required}";
                $tableColumns[] = "Tables\\Columns\\TextColumn::make('{$rel}.nome')->label('" . Str::headline($rel) . "')->searchable()";
            } elseif (($field['type'] ?? null) === 'text') {
                $formFields[] = "Forms\\Components\\Textarea::make('{$name}')->label('" . Str::headline($name) . "'){$required}";
                $tableColumns[] = "Tables\\Columns\\TextColumn::make('{$name}')->limit(40)->toggleable()";
            } elseif (($field['type'] ?? null) === 'date') {
                $formFields[] = "Forms\\Components\\DatePicker::make('{$name}')->label('" . Str::headline($name) . "'){$required}";
                $tableColumns[] = "Tables\\Columns\\TextColumn::make('{$name}')->date()->sortable()";
            } elseif (($field['type'] ?? null) === 'decimal') {
                $formFields[] = "Forms\\Components\\TextInput::make('{$name}')->numeric()->label('" . Str::headline($name) . "'){$required}";
                $tableColumns[] = "Tables\\Columns\\TextColumn::make('{$name}')->money('BRL')->sortable()";
            } else {
                $formFields[] = "Forms\\Components\\TextInput::make('{$name}')->label('" . Str::headline($name) . "'){$required}";
                $tableColumns[] = "Tables\\Columns\\TextColumn::make('{$name}')->searchable()->sortable()";
            }
        }

        $formCode = implode(",\n                    ", $formFields);
        $columnsCode = implode(",\n                    ", $tableColumns);

        $listPage = 'List' . Str::studly($slug);

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\\{$resource}\\Pages;
use App\Models\\{$model};
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class {$resource} extends Resource
{
    protected static ?string \$model = {$model}::class;

    protected static ?string \$navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string \$navigationGroup = 'Factory';

    protected static ?string \$navigationLabel = '{$label}';

    public static function form(Form \$form): Form
    {
        return \$form
            ->schema([
                {$formCode}
            ]);
    }

    public static function table(Table \$table): Table
    {
        return \$table
            ->columns([
                {$columnsCode}
            ])
            ->filters([
                Tables\\Filters\\TrashedFilter::make(),
            ])
            ->actions([
                Tables\\Actions\\ViewAction::make(),
                Tables\\Actions\\EditAction::make(),
            ])
            ->bulkActions([
                Tables\\Actions\\BulkActionGroup::make([
                    Tables\\Actions\\DeleteBulkAction::make(),
                    Tables\\Actions\\ForceDeleteBulkAction::make(),
                    Tables\\Actions\\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\\{$listPage}::route('/'),
            'create' => Pages\\Create{$model}::route('/create'),
            'view' => Pages\\View{$model}::route('/{record}'),
            'edit' => Pages\\Edit{$model}::route('/{record}/edit'),
        ];
    }
}

PHP;
    }

    protected function createPageCode(string $model, string $resource): string
    {
        return <<<PHP
<?php

namespace App\Filament\Resources\\{$resource}\\Pages;

use App\Filament\Resources\\{$resource};
use Filament\Resources\Pages\CreateRecord;

class Create{$model} extends CreateRecord
{
    protected static string \$resource = {$resource}::class;
}

PHP;
    }

    protected function editPageCode(string $model, string $resource): string
    {
        return <<<PHP
<?php

namespace App\Filament\Resources\\{$resource}\\Pages;

use App\Filament\Resources\\{$resource};
use Filament\Resources\Pages\EditRecord;

class Edit{$model} extends EditRecord
{
    protected static string \$resource = {$resource}::class;
}

PHP;
    }

    protected function viewPageCode(string $model, string $resource): string
    {
        return <<<PHP
<?php

namespace App\Filament\Resources\\{$resource}\\Pages;

use App\Filament\Resources\\{$resource};
use Filament\Resources\Pages\ViewRecord;

class View{$model} extends ViewRecord
{
    protected static string \$resource = {$resource}::class;
}

PHP;
    }

    protected function listPageCode(string $model, string $resource, string $slug): string
    {
        $listPage = 'List' . Str::studly($slug);

        return <<<PHP
<?php

namespace App\Filament\Resources\\{$resource}\\Pages;

use App\Filament\Resources\\{$resource};
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class {$listPage} extends ListRecords
{
    protected static string \$resource = {$resource}::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\\CreateAction::make(),
        ];
    }
}

PHP;
    }

    protected function policyCode(string $model): string
    {
        return <<<PHP
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\\{$model};

class {$model}Policy
{
    public function viewAny(User \$user): bool { return true; }
    public function view(User \$user, {$model} \$record): bool { return true; }
    public function create(User \$user): bool { return true; }
    public function update(User \$user, {$model} \$record): bool { return true; }
    public function delete(User \$user, {$model} \$record): bool { return true; }
    public function restore(User \$user, {$model} \$record): bool { return true; }
    public function forceDelete(User \$user, {$model} \$record): bool { return true; }
}

PHP;
    }

    protected function seederCode(string $model): string
    {
        return <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class {$model}Seeder extends Seeder
{
    public function run(): void
    {
        //
    }
}

PHP;
    }
}
