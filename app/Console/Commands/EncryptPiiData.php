<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptPiiData extends Command
{
    protected $signature   = 'encrypt:pii {--dry-run : Preview what would be encrypted without writing}';
    protected $description = 'Encrypt all PII text fields in the database using the app encryption key';

    private array $tables = [
        'users'                 => ['name'],
        'transactions'          => ['description', 'notes', 'reference_number', 'amount'],
        'accounts'              => ['name', 'bank_name', 'account_number', 'balance'],
        'income_sources'        => ['name', 'description', 'amount'],
        'savings_goals'         => ['name', 'description', 'target_amount', 'current_amount'],
        'loans'                 => ['name', 'lender', 'notes', 'principal_amount', 'remaining_balance', 'monthly_payment'],
        'bills'                 => ['name', 'payee', 'notes', 'amount'],
        'budgets'               => ['name', 'total_budget'],
        'categories'            => ['name'],
        'savings_contributions' => ['notes', 'amount'],
        'loan_payments'         => ['reference_number', 'notes', 'amount', 'principal_portion', 'interest_portion'],
        'bill_payments'         => ['reference_number', 'notes', 'amount'],
        'budget_categories'     => ['allocated_amount'],
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be written.');
        }

        $grandTotal = 0;

        foreach ($this->tables as $table => $columns) {
            $this->line("  <fg=cyan>{$table}</>");
            $count = 0;

            DB::table($table)->orderBy('id')->chunk(500, function ($rows) use ($table, $columns, $dryRun, &$count) {
                foreach ($rows as $row) {
                    $updates = [];

                    foreach ($columns as $col) {
                        $value = $row->$col ?? null;

                        if ($value === null || $value === '') {
                            continue;
                        }

                        if (!$this->isEncrypted($value)) {
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

        if ($grandTotal === 0) {
            $this->info('All PII fields are already encrypted. Nothing to do.');
        } else {
            $verb = $dryRun ? 'would be encrypted' : 'encrypted successfully';
            $this->info("{$grandTotal} rows {$verb}.");
        }

        return self::SUCCESS;
    }

    private function isEncrypted(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        try {
            Crypt::decryptString($value);
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
