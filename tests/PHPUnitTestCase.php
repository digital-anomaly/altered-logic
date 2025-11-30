<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Tests;

use DigitalAnomaly\AlteredLogic\Support\ValueStore;
use PHPUnit\Framework\TestCase;

/**
 * The PHPUnit test case.
 */
abstract class PHPUnitTestCase extends TestCase
{
    /**
     * Clean up after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // remove the singleton instance of ValueStore to prevent singleton state leakage
        // (for tests operating in an environment without a framework to manage the singleton instance)
        ValueStore::cleanUp();

        parent::tearDown();
    }
}
