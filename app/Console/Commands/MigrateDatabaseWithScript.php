<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class MigrateDatabaseWithScript extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:script';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate database and generate its script';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Artisan::call('migrate', [
            '--pretend' => true,
            '--no-ansi' => true,
        ]);

        $output = $this->formatOutputToSQL(Artisan::output());

        $filePath = base_path('database/database.sql');
        File::put($filePath, $output);

        $this->info("Migration script has been saved to: /database/database.sql");
    }

    private function formatOutputToSQL(string $output): string
    {
        $output = str_replace('⇂', '', $output);
        $lines = explode(PHP_EOL, $output);

        $sqlCommands = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^(create|alter|drop|insert|update|delete|truncate)/i', $line)) {
                $sqlCommands[] = $line . ';';
            }
        }

        return implode(PHP_EOL, $sqlCommands);
    }
}
