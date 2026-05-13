<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OwenIt\Auditing\Models\Audit;

class PruneAudits extends Command
{
    protected $signature = 'audits:prune {--days=90 : Number of days to retain}';

    protected $description = 'Delete audit records older than the retention period';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $deleted = Audit::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Pruned {$deleted} audit records older than {$days} days.");

        return self::SUCCESS;
    }
}
