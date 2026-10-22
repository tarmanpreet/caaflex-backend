<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PhpRuntimeTest extends TestCase
{
    public function test_php_runtime_includes_the_critical_security_fixes(): void
    {
        $this->assertTrue(
            version_compare(PHP_VERSION, '8.5.12', '>='),
            'PHP 8.5.12 or newer is required to include the critical security fixes.'
        );
    }
}
