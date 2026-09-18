<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\App;
use PHPUnit\Framework\TestCase;

final class FlashTest extends TestCase
{
    public function testFlashIsConsumedOnce(): void
    {
        App::flash('success', 'Message de test');

        $this->assertSame(['success' => 'Message de test'], App::flashes());
        $this->assertSame([], App::flashes());
    }
}