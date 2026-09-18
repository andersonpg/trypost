<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $envPath = base_path('.env');
        if (file_exists($envPath) && is_writable($envPath)) {
            $content = file_get_contents($envPath);
            if (preg_match('/^BROADCAST_CONNECTION=reverb/m', $content)) {
                $content = preg_replace('/^BROADCAST_CONNECTION=reverb/m', 'BROADCAST_CONNECTION=null', $content);
                file_put_contents($envPath, $content);
            }
        }
    }

    public function down(): void
    {
        // No-op
    }
};
