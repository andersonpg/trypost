<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        $publicStoragePath = storage_path('app/public');
        $privateStoragePath = storage_path('app/private');
        $publicSymlink = public_path('storage');

        // 1. Ensure storage/app/public exists
        if (! File::isDirectory($publicStoragePath)) {
            File::makeDirectory($publicStoragePath, 0775, true, true);
        }

        // 2. Copy any files/folders from private (medias, social-accounts, etc.) to public
        if (File::isDirectory($privateStoragePath)) {
            foreach (File::directories($privateStoragePath) as $dir) {
                $dirName = basename($dir);
                if ($dirName === 'chunks') {
                    continue;
                }
                $targetDir = $publicStoragePath . '/' . $dirName;
                if (! File::isDirectory($targetDir)) {
                    File::makeDirectory($targetDir, 0775, true, true);
                }
                File::copyDirectory($dir, $targetDir);
            }
        }

        // 3. Fix permissions on storage/app/public so web server (nginx/www-data) can read
        @chmod($publicStoragePath, 0775);
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($publicStoragePath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    @chmod($item->getPathname(), 0775);
                } else {
                    @chmod($item->getPathname(), 0664);
                }
            }
        } catch (\Throwable) {
            // Ignore if iterator hits an inaccessible node
        }

        // 4. Create the public/storage symlink if missing or broken
        if (is_link($publicSymlink) && ! file_exists($publicSymlink)) {
            @unlink($publicSymlink);
        }
        if (! file_exists($publicSymlink) && ! is_link($publicSymlink)) {
            @symlink($publicStoragePath, $publicSymlink);
        }

        // 5. Update .env to use FILESYSTEM_DISK=public
        $envPath = base_path('.env');
        if (file_exists($envPath) && is_writable($envPath)) {
            $env = file_get_contents($envPath);
            if (preg_match('/^FILESYSTEM_DISK=local/m', $env)) {
                $env = preg_replace('/^FILESYSTEM_DISK=local/m', 'FILESYSTEM_DISK=public', $env);
                file_put_contents($envPath, $env);
            }
        }
    }

    public function down(): void
    {
        // No-op
    }
};
