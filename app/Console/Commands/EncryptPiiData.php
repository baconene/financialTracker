<?php

namespace App\Console\Commands;

use App\Services\UserEncryptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptPiiData extends Command
{
    protected $signature   = 'encrypt:pii {--dry-run : Preview what would be encrypted without writing}';
    protected $description = 'Encrypt PII fields; financial amounts use per-user keys, other PII uses APP_KEY';

    // String PII → encrypted with APP_KEY
    private array $appKeyTables = [
        'users'                 => ['name'],
        'accounts'              => ['name', 'bank_name', 'account_number'],
        'transactions'          => ['description', 'notes', 'reference_number'],
        'income_sources'        => ['name', 'description'],
        'savings_goals'         => ['name', 'description'],
        'loans'                 => ['name', 'lender', 'notes'],
        'bills'                 => ['name', 'payee', 'notes'],
        'budgets'               => ['name'],
        'categories'            => ['name'],
        'savings_contributions' => ['notes'],
        'loan_payments'         => ['reference_number', 'notes'],
        'bill_payments'         => ['reference_number', 'notes'],
    ];

    // Financial amounts → encrypted with per-user key (table must have user_id column)
    private array $userKeyTables = [
        'accounts'              => ['balance'],
        'transactions'          => ['amount'],
        'savings_goals'         => ['target_amount', 'current_amount'],
        'loans'                 => ['principal_amount', 'remaining_balance', 'monthly_payment'],
        'bills'                 => ['amount'],
        'income_sources'        => ['amount'],
        'savings_contributions' => ['amount'],
        'loan_payments'         => ['amount', 'principal_portion', 'interest_portion'],
        'bill_payments'         => ['amount'],
        'budgets'               => ['total_budget'],
        'budget_categories'     => ['allocated_amount'],
    ];

    public function handle(): int
    {
        $dryRun  = $this->option('dry-run');
        $service = app(UserEncryptionService::class);

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be written.');
        }

        // Provision per-user keys for any user that doesn't have one yet
        $this->line('Provisioning per-user encryption keys…');
        $missing = DB::table('users')->whereNull('data_key')->pluck('id');
        foreach ($missing as $userId) {
            if (!$dryRun) {
                $service->generateKeyForUser($userId);
            }
        }
        $this->line("  → {$missing->count()} key(s) generated");

        $grandTotal = 0;

        $this->newLine();
        $this->line('<fg=yellow>APP_KEY fields:</>');
        foreach ($this->appKeyTables as $table => $columns) {
            $this->line("  <fg=cyan>{$table}</>");
            $count = 0;

            DB::table($table)->orderBy('id')->chunk(500, function ($rows) use ($columns, $dryRun, $table, &$count) {
                foreach ($rows as $row) {
                    $updates = [];

                    foreach ($columns as $col) {
                        $value = $row->$col ?? null;
                        if ($value === null || $value === '') continue;
                        if (!$this->isAppKeyEncrypted($value)) {
                            $updates[$col] = Crypt::encryptString($value);
                        }
                    }

                    if (!empty($updates)) {
                        $count++;
                        if (!$dryRun) {
                            DB::table($table)->where('id', $row->id)->update($updates);
                        }
                    }
                }
            });

            $verb = $dryRun ? 'would encrypt' : 'encrypted';
            $this->line("    → {$count} rows {$verb}");
            $grandTotal += $count;
        }

        $this->newLine();
        $this->line('<fg=yellow>Per-user key fields:</>');
        foreach ($this->userKeyTables as $table => $columns) {
            $this->line("  <fg=cyan>{$table}</>");
            $count = 0;

            DB::table($table)->orderBy('id')->chunk(500, function ($rows) use ($columns, $dryRun, $table, $service, &$count) {
                foreach ($rows as $row) {
                    $userId = $row->user_id ?? null;
                    if (!$userId) continue;

                    $updates = [];

                    foreach ($columns as $col) {
                        $value = $row->$col ?? null;
                        if ($value === null || $value === '') continue;

                        if (!$this->isUserKeyEncrypted($value, (int) $userId, $service)) {
                            // Decrypt APP_KEY-encrypted values before re-encrypting with user key
                            $plain = $this->isAppKeyEncrypted($value)
                                ? Crypt::decryptString($value)
                                : $value;
                            $updates[$col] = $service->encrypterFor((int) $userId)->encryptString((string) $plain);
                        }
                    }

                    if (!empty($updates)) {
                        $count++;
                        if (!$dryRun) {
                            DB::table($table)->where('id', $row->id)->update($updates);
                        }
                    }
                }
            });

            $verb = $dryRun ? 'would encrypt/rekey' : 'encrypted/rekeyed';
            $this->line("    → {$count} rows {$verb}");
            $grandTotal += $count;
        }

        $this->newLine();

        if ($grandTotal === 0) {
            $this->info('All PII fields are already encrypted. Nothing to do.');
        } else {
            $verb = $dryRun ? 'would be processed' : 'processed successfully';
            $this->info("{$grandTotal} rows {$verb}.");
        }

        return self::SUCCESS;
    }

    private function isAppKeyEncrypted(?string $value): bool
    {
        if ($value === null || $value === '') return false;
        try {
            Crypt::decryptString($value);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private function isUserKeyEncrypted(?string $value, int $userId, UserEncryptionService $service): bool
    {
        if ($value === null || $value === '') return false;
        try {
            $service->encrypterFor($userId)->decryptString($value);
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
