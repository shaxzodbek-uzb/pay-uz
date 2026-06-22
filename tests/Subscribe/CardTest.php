<?php

namespace Goodoneuz\PayUz\Tests\Subscribe;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Subscribe\Card;

/**
 * Card value object: result parsing and the verified/recurrent flags.
 */
class CardTest extends TestCase
{
    /** @test */
    public function it_parses_the_card_envelope_from_a_result()
    {
        $card = Card::fromResult(['card' => [
            'number'    => '860006******6311',
            'expire'    => '03/99',
            'token'     => 'tok_abc',
            'recurrent' => true,
            'verify'    => false,
            'type'      => 'UZCARD',
        ]]);

        $this->assertSame('860006******6311', $card->number());
        $this->assertSame('03/99', $card->expire());
        $this->assertSame('tok_abc', $card->token());
        $this->assertTrue($card->isRecurrent());
        $this->assertFalse($card->isVerified());
        $this->assertSame('UZCARD', $card->type());
    }

    /** @test */
    public function it_accepts_a_bare_card_object_too()
    {
        $card = Card::fromResult(['token' => 'tok_x', 'verify' => true]);

        $this->assertSame('tok_x', $card->token());
        $this->assertTrue($card->isVerified());
    }

    /** @test */
    public function type_is_null_when_absent()
    {
        $card = Card::fromResult(['card' => ['token' => 'tok']]);
        $this->assertNull($card->type());
    }

    /** @test */
    public function to_array_carries_only_safe_fields()
    {
        $array = Card::fromResult(['card' => ['number' => '8600****0000', 'token' => 'tok', 'verify' => true]])->toArray();

        $this->assertSame('tok', $array['token']);
        $this->assertTrue($array['verify']);
        $this->assertArrayNotHasKey('cvv', $array);
    }
}
