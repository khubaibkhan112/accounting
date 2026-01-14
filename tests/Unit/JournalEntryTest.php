<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JournalEntryTest extends TestCase
{
    // use RefreshDatabase;

    public function test_it_checks_if_entry_is_balanced()
    {
        $entry = new JournalEntry([
            'total_debit' => 100.00,
            'total_credit' => 100.00,
        ]);

        $this->assertTrue($entry->isBalanced());

        $unbalancedEntry = new JournalEntry([
            'total_debit' => 100.00,
            'total_credit' => 90.00,
        ]);

        $this->assertFalse($unbalancedEntry->isBalanced());
    }

    public function test_it_checks_floating_point_precision()
    {
        $entry = new JournalEntry([
            'total_debit' => 100.00,
            'total_credit' => 100.004, // Less than 0.01 diff
        ]);

        $this->assertTrue($entry->isBalanced());
    }
}
