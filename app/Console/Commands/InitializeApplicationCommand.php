<?php

namespace App\Console\Commands;

use App\Console\Concerns\RendersAsciiParrot;
use Illuminate\Console\Command;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PDOException;

class InitializeApplicationCommand extends Command
{
    use RendersAsciiParrot;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:initialize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure the Application Environment';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->drawMurderousWingedDevil();

        $config = $this->getConfig();
        $replacements = $this->getReplacements($config);

        $this->info('The following values will get replaced in the .env file:');
        $this->table(['Old', 'New'], Arr::map($replacements, fn ($value, $key) => [$key, $value]));

        if (! $this->confirm('Does this look ok?', true)) {
            $this->warn('Replacement cancelled.');

            return self::FAILURE;
        }

        $this->info('Replacing values in the .env file.');
        $this->replaceInEnvironmentFile($replacements);

        $this->info('Replacing values in the .env.example file (don\'t worry, we won\'t insert sensitive data)');
        $this->replaceInEnvironmentExampleFile($replacements);

        // Reconnect the Database since the credentials have been updated
        config()->set('database.connections.mysql.username', $config['db_username']);
        config()->set('database.connections.mysql.password', $config['db_password']);
        config()->set('database.connections.mysql.database', $config['db_name']);
        DB::reconnect();

        if (! $this->confirm('Would you like to migrate and seed the database? (You should ensure the database exists before accepting this)')) {
            return self::SUCCESS;
        }

        try {
            $this->call(
                command: FreshCommand::class,
                arguments: [
                    '--seed',
                ]
            );
        } catch (PDOException $e) {
            $this->error('Database migrate and seed failed, please fix the error below and run "php artisan migrate:fresh --seed" manually.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function getConfig(): array
    {
        $appName = $this->ask('What is the name of the project?');

        $config = [
            'app_name' => $appName,
            'app_url' => $this->ask('What is the local application url?', Str::slug($appName).'.localhost'),
            'db_name' => $this->ask('What is the name of the Database?', Str::snake($appName)),
            'db_username' => $this->ask('What user should we use to connect to the database server?', 'root'),
            'db_password' => $this->ask('What password should we use to connect to the database server?', 'root'),
            'has_filemaker' => $this->confirm('Does the application connect to FileMaker?'),
        ];

        if ($config['has_filemaker']) {
            $config = array_merge(
                $config,
                [
                    'fm_hostname' => $this->ask('What is the hostname of the FileMaker server?'),
                    'fm_database' => $this->ask('What is the name of the FileMaker database file?'),
                    'fm_username' => $this->ask('What username should we use to connect to the FileMaker server?'),
                    'fm_password' => $this->ask('What password should we use to connect to the FileMaker server?'),
                ]
            );
        }

        return $config;
    }

    protected function getReplacements(array $config): array
    {
        $replacements = [
            'APP_NAME=Laravel' => "APP_NAME=\"{$config['app_name']}\"",
            'APP_URL=http://localhost' => "APP_URL=http://{$config['app_url']}",
            'DB_DATABASE=laravel' => "DB_DATABASE={$config['db_name']}",
            'DB_USERNAME=root' => "DB_USERNAME={$config['db_username']}",
            'DB_PASSWORD=' => "DB_PASSWORD={$config['db_password']}",
        ];

        if ($config['has_filemaker']) {
            $replacements = array_merge(
                $replacements,
                [
                    'FM_DATABASE=' => "FM_DATABASE={$config['fm_database']}",
                    'FM_USERNAME=' => "FM_USERNAME={$config['fm_password']}",
                    'FM_PASSWORD=' => "FM_PASSWORD={$config['fm_username']}",
                    'FM_HOSTNAME=' => "FM_HOSTNAME={$config['fm_hostname']}",
                ]
            );
        }

        return $replacements;
    }

    protected function replaceInEnvironmentFile(array $replacements): void
    {
        File::replaceInFile(
            search: array_keys($replacements),
            replace: array_values($replacements),
            path: base_path('.env')
        );
    }

    protected function replaceInEnvironmentExampleFile(array $replacements): void
    {
        $replacements = Arr::except(
            array: $replacements,
            keys: [
                'DB_PASSWORD=',
                'FM_USERNAME=',
                'FM_PASSWORD=',
            ]
        );

        File::replaceInFile(
            search: array_keys($replacements),
            replace: array_values($replacements),
            path: base_path('.env.example')
        );
    }
}
