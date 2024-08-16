<?php
namespace SyntechTable\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeTableCommand extends Command
{
    protected $signature = 'syntechtable:make {name}';

    protected $description = 'Create a new SyntechTable';

    public function handle()
    {
        $name = $this->argument('name');

        $stub = File::get(__DIR__ . '/stubs/table.stub');

        $stub = str_replace('DummyTable', $name, $stub);

        File::put(app_path("SyntechTable/{$name}.php"), $stub);

        $this->info("Table {$name} created successfully.");
    }
}
