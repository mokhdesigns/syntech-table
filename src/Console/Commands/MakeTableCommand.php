<?php

namespace Syntech\Syntechtable\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeTableCommand extends Command
{
    protected $signature = 'syntechtable:make {name}';
    protected $description = 'Create a new Syntech table class';

    public function handle()
    {
        $name = $this->argument('name');
        $path = $this->getPath($name);

        if (File::exists($path)) {
            $this->error("File {$path} already exists!");
            return;
        }

        // Ensure the directory exists
        File::ensureDirectoryExists(dirname($path));

        // Get the class name and namespace
        $stub = $this->getStub();
        $namespace = $this->getNamespace($name);
        $class = $this->getClass($name);

        // Replace placeholders in the stub
        $content = str_replace(
            ['DummyNamespace', 'DummyClass'],
            [$namespace, $class],
            $stub
        );

        File::put($path, $content);

        $this->info("Table class {$class} created successfully.");
    }

    protected function getPath($name)
    {
        $name = str_replace('\\', '/', $name);
        return app_path("SyntechTable/{$name}.php");
    }

    protected function getStub()
    {
        return File::get(__DIR__ . '/../../stubs/Table.stub');
    }

    protected function getNamespace($name)
    {
        $namespace = str_replace('/', '\\', dirname($name));
        return 'App\\SyntechTable' . ($namespace === '.' ? '' : "\\{$namespace}");
    }

    protected function getClass($name)
    {
        return basename(str_replace('\\', '/', $name));
    }
}
