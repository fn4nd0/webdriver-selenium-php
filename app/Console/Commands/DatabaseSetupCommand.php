<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DatabaseSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the database and execute the migrations';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Create the database
        $databaseName = config('database.connections.mysql.database');
        $charset = config('database.connections.mysql.charset');
        $collation = config('database.connections.mysql.collation');

        config(['database.connections.mysql.database' => null]);

        DB::statement("CREATE DATABASE IF NOT EXISTS $databaseName CHARACTER SET $charset COLLATE $collation;");
        DB::statement("USE $databaseName;");

        config(['database.connections.mysql.database' => $databaseName]);

        // Execute the migrations
        Artisan::call('migrate', ['--force' => true]);

        $this->info('Database and migrations created sucessfully.');
    }
}
