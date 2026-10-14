<?php

namespace App\Console\Commands\fileSupport;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class migrateUsersAvatarsFromS3ToLocal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-users-avatars-from-s3-to-local';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $localDisk = 'usersAvatars';
        $s3Disk = 's3usersAvatars';

        $this->info('Fetching file list from S3...');
        $allFiles = Storage::disk($s3Disk)->allFiles();

        $total = count($allFiles);
        $this->info("Found {$total} files on S3. Starting migration to local...");
        $this->output->progressStart($total);

        $createdDirs = [];

        foreach ($allFiles as $remotePath) {
            $localPath = $remotePath;
            $directory = dirname($localPath);

            if (! isset($createdDirs[$directory])) {
                try {
                    Storage::disk($localDisk)->makeDirectory($directory);
                } catch (Throwable $e) {
                    Log::info("Failed to create directory {$directory}: {$e->getMessage()}");
                }
                $createdDirs[$directory] = true;
            }

            if (Storage::disk($localDisk)->exists($localPath)) {
                $this->output->progressAdvance();

                continue;
            }

            $stream = null;
            try {
                $stream = Storage::disk($s3Disk)->readStream($remotePath);
                if ($stream === false) {
                    Log::info("Failed to read stream for {$remotePath}");
                    $this->output->progressAdvance();

                    continue;
                }

                $result = Storage::disk($localDisk)->writeStream($localPath, $stream);
                if (! $result) {
                    Log::info("Failed to write file {$localPath}");
                }
            } catch (Throwable $e) {
                $this->warn("Exception for {$remotePath}: {$e->getMessage()}");
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        $this->info('Migration completed.');
    }
}
