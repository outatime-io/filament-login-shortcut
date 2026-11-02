<?php

declare(strict_types=1);

namespace OutatimeIo\FilamentLoginShortcut\Tests\Feature;

use Illuminate\Foundation\Bootstrap\HandleExceptions;
use PHPStan\Testing\TypeInferenceTestCase;

final class LoginShortcutTypeInferenceTest extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__.'/../../vendor/larastan/larastan/extension.neon'];
    }

    protected function tearDown(): void
    {
        HandleExceptions::flushState($this);

        parent::tearDown();
    }

    public function test_query_callbacks_preserve_the_configured_model(): void
    {
        foreach (self::gatherAssertTypes(__DIR__.'/../Fixtures/login-shortcut-types.php') as $assert) {
            $this->assertFileAsserts(...$assert);
        }
    }
}
