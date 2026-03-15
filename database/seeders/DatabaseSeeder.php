<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Check if we should run SQL initialization
        if (config('app.env') === 'production' && $this->shouldRunSqlInit()) {
            $this->runSqlInitialization();
        } else {
            // Run normal seeders for development
            $this->runNormalSeeders();
        }
    }

    /**
     * Check if we should run SQL initialization
     */
    private function shouldRunSqlInit(): bool
    {
        // Check if migrations table exists and is empty
        try {
            $migrationCount = DB::table('migrations')->count();
            return $migrationCount === 0;
        } catch (\Exception $e) {
            // If migrations table doesn't exist, run SQL init
            return true;
        }
    }

    /**
     * Run SQL initialization from file
     */
    private function runSqlInitialization(): void
    {
        $sqlFile = database_path('sql/init_database.sql');
        
        if (!File::exists($sqlFile)) {
            $this->command->error("SQL initialization file not found: {$sqlFile}");
            return;
        }

        $this->command->info('Running SQL database initialization...');
        
        try {
            $sql = File::get($sqlFile);
            
            // Split SQL into individual statements
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($statement) {
                    return !empty($statement) && !preg_match('/^--/', $statement);
                }
            );

            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    DB::unprepared($statement);
                }
            }

            $this->command->info('✅ SQL database initialization completed successfully!');
            
        } catch (\Exception $e) {
            $this->command->error("❌ SQL initialization failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Run normal seeders for development
     */
    private function runNormalSeeders(): void
    {
        $this->command->info('Running normal database seeders...');
        
        // Seed default data (capabilities, geo data, roles, users, organizations)
        $this->call([
            DefaultDataSeeder::class,
        ]);

        // Seed demo data (sau DefaultDataSeeder)
        $this->call([
            DemoDataSeeder::class,
        ]);
    }
}
