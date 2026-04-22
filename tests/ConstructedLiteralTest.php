<?php

namespace alcamo\data_element;

use alcamo\exception\InvalidType;
use alcamo\rdf_literal\{IntegerLiteral, LangStringLiteral};
use PHPUnit\Framework\TestCase;

class ConstructedLiteralTest extends TestCase
{
    /**
     * @dataProvider basicsProvider
     */
    public function testBasics($value, $expectedString, $expectedDigest): void
    {
        $literal = new ConstructedLiteral($value);

        $this->assertSame(count($value), count($literal));

        $this->assertSame($expectedString, (string)$literal);

        $this->assertSame($expectedDigest, $literal->getDigest());
    }

    public function basicsProvider(): array
    {
        return [
            [ [], '', '' ],
            [
                [
                    new LangStringLiteral('ciao', 'it'),
                    null,
                    new IntegerLiteral(42)
                ],
                'ciao||42',
                '"ciao"@it||42'
            ]
        ];
    }

    public function testException(): void
    {
        $this->expectException(InvalidType::class);
        $this->expectExceptionMessage(
            'Invalid type "string", expected one of '
                . '"alcamo\rdf_literal\LiteralInterface"'
        );

        new ConstructedLiteral([ new IntegerLiteral(0), 'foo' ]);
    }
}
