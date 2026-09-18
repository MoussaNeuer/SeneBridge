<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\InvoiceService;
use PHPUnit\Framework\TestCase;

final class InvoiceServiceTest extends TestCase
{
    public function testStatusFallsBackWhenNothingPaid(): void
    {
        $this->assertSame('envoyee', InvoiceService::statusFor(0, 250000));
        $this->assertSame('envoyee', InvoiceService::statusFor('0', '250000'));
        $this->assertSame('brouillon', InvoiceService::statusFor(0, 250000, 'brouillon'));
    }

    public function testStatusPaidInFull(): void
    {
        $this->assertSame('payee', InvoiceService::statusFor(250000, 250000));
        $this->assertSame('payee', InvoiceService::statusFor(250001, 250000));
    }

    public function testStatusPartialPayment(): void
    {
        $this->assertSame('partielle', InvoiceService::statusFor(100000, 250000));
        $this->assertSame('partielle', InvoiceService::statusFor('100000.50', '250000'));
    }
}