<?php

namespace App\Console\Commands;

use App\Jobs\ProcessLocationBusinessDataTasks as ProcessLocationBusinessDataTasksJob;
use Illuminate\Console\Command;

class ProcessLocationBusinessDataTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'location:process-business-data-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process ready location business data tasks from DataForSeo API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing location business data tasks...');
        
        try {
            ProcessLocationBusinessDataTasksJob::dispatchSync();
            $this->info('Successfully processed location business data tasks');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to process location business data tasks: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
