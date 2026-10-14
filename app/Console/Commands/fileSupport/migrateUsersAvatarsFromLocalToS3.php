<?php

namespace App\Console\Commands\fileSupport;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class migrateUsersAvatarsFromLocalToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-users-avatars-from-local-to-s3';

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

        $this->info('Fetching file list from local storage...');
        $allFiles = Storage::disk($localDisk)->allFiles();

        $total = count($allFiles);
        $this->info("Found {$total} files locally. Starting migration to S3...");
        $this->output->progressStart($total);

        foreach ($allFiles as $localPath) {
            $remotePath = $localPath;

            if (Storage::disk($s3Disk)->exists($remotePath)) {
                $this->output->progressAdvance();

                continue;
            }

            $stream = null;

            try {
                $stream = Storage::disk($localDisk)->readStream($localPath);

                if ($stream === false) {
                    Log::info("Failed to read {$localPath}");

                    continue;
                }

                $result = Storage::disk($s3Disk)->writeStream($remotePath, $stream);

                if (! $result) {
                    Log::info("Failed to write {$remotePath}");
                }
            } catch (Throwable $e) {
                report($e);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
                $this->output->progressAdvance();
            }
        }

        $this->output->progressFinish();
        $this->info('Migration completed.');
    }
}
