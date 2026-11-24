<?php

namespace Tests\Unit;

use App\Support\WhatsAppPhone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WhatsAppPhoneTest extends TestCase
{
    /**
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function phoneNumbers(): array
    {
        return [
            'brazilian mobile' => ['11999999999', '+5511999999999'],
            'formatted brazilian mobile' => ['(11) 99999-9999', '+5511999999999'],
            'brazilian with country code' => ['+55 11 99999-9999', '+5511999999999'],
            'brazilian landline' => ['1133334444', '+551133334444'],
            'international number' => ['16505551234', '+16505551234'],
            'international with prefix' => ['0016505551234', '+16505551234'],
            'empty value' => ['', null],
            'null value' => [null, null],
        ];
    }

    #[DataProvider('phoneNumbers')]
    public function test_normalizes_phone_numbers(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, WhatsAppPhone::normalize($input));
    }

    public function test_candidates_include_common_stored_formats(): void
    {
        $candidates = WhatsAppPhone::candidates('5511999999999');

        $this->assertContains('+5511999999999', $candidates);
        $this->assertContains('5511999999999', $candidates);
        $this->assertContains('11999999999', $candidates);
        $this->assertContains('(11) 99999-9999', $candidates);
        $this->assertContains('11 99999-9999', $candidates);
    }
}
