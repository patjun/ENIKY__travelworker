<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CiFailingCheckTest extends TestCase
{
    public function test_ci_reports_a_failing_test(): void
    {
        $this->assertTrue(false);
    }
}
