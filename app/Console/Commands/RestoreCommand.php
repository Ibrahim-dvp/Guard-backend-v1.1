<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'restore {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore application to fresh state: migrate fresh, seed, clear caches, reset permissions, and optimize';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Guard Backend Restore Process...');
        $this->newLine();

        // Check if we should force or ask for confirmation
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  This will reset your database and clear all caches. Are you sure you want to continue?')) {
                $this->warn('Restore process cancelled.');
                return Command::FAILURE;
            }
        }

        $this->info('🗄️  Step 1: Resetting Database...');
        $this->executeCommand('migrate:fresh --seed --force', 'Database reset and seeded');

        $this->info('🧹 Step 2: Clearing Application Caches...');
        $this->executeCommand('config:clear', 'Configuration cache cleared');
        $this->executeCommand('cache:clear', 'Application cache cleared');
        $this->executeCommand('route:clear', 'Route cache cleared');
        $this->executeCommand('view:clear', 'View cache cleared');
        $this->executeCommand('event:clear', 'Event cache cleared');

        $this->info('🔑 Step 3: Resetting Permissions Cache...');
        $this->executeCommand('permission:cache-reset', 'Permissions cache reset');

        $this->info('🚀 Step 4: Optimizing Application...');
        $this->executeCommand('optimize:clear', 'All optimization caches cleared');

        $this->info('📦 Step 5: Regenerating Optimizations...');
        $this->executeCommand('config:cache', 'Configuration cached');
        $this->executeCommand('route:cache', 'Routes cached');
        $this->executeCommand('view:cache', 'Views cached');

        $this->info('🧹 Step 6: Additional Cleanup...');
        $this->executeCommand('queue:clear', 'Queue cleared', false); // May not exist
        $this->executeCommand('storage:link', 'Storage linked', false); // May already exist

        $this->newLine();
        $this->info('✅ Guard Backend Restore Process Completed Successfully!');
        $this->newLine();
        
        // Display summary
        $this->displaySummary();

        return Command::SUCCESS;
    }

    /**
     * Execute an Artisan command and display the result.
     */
    private function executeCommand(string $command, string $description, bool $required = true): void
    {
        try {
            $this->line("   → Running: {$command}");
            
            $exitCode = Artisan::call($command);
            
            if ($exitCode === 0) {
                $this->line("   ✅ {$description}", null, 'v');
            } else {
                if ($required) {
                    $this->error("   ❌ Failed: {$description}");
                    throw new \Exception("Command failed: {$command}");
                } else {
                    $this->warn("   ⚠️  Warning: {$description} (non-critical)");
                }
            }
        } catch (\Exception $e) {
            if ($required) {
                $this->error("   ❌ Error running {$command}: " . $e->getMessage());
                throw $e;
            } else {
                $this->warn("   ⚠️  Could not run {$command}: " . $e->getMessage());
            }
        }
        
        $this->newLine();
    }

    /**
     * Display a summary of what was restored.
     */
    private function displaySummary(): void
    {
        $this->info('📋 Restore Summary:');
        $this->line('   • Database: Fresh migration with seeders');
        $this->line('   • Cache: All caches cleared and regenerated');
        $this->line('   • Permissions: Reset and refreshed');
        $this->line('   • Config: Cleared and cached');
        $this->line('   • Routes: Cleared and cached');
        $this->line('   • Views: Cleared and cached');
        $this->line('   • Storage: Linked');
        $this->line('   • Queue: Cleared');
        $this->newLine();
        
        $this->comment('🎉 Your Guard Backend application is now in a fresh, optimized state!');
        $this->comment('💡 You can now start using the application with fresh data.');
        $this->newLine();
        
        // Show some useful next steps
        $this->info('📚 Next Steps:');
        $this->line('   • Test API endpoints: GET /api/v1/teams');
        $this->line('   • Login with seeded accounts (check database seeders)');
        $this->line('   • Verify permissions are working correctly');
        $this->newLine();
    }
}
