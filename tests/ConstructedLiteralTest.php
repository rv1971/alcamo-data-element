<?php

namespace alcamo\data_element;

use alcamo\exception\InvalidType;
use alcamo\rdf_literal\{
    IntegerLiteral,
    DigitStringLiteral,
    LangStringLiteral,
    NonNegativeIntegerLiteral,
    StringLiteral
};
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

    /**
     * @dataProvider equalsProvider
     */
    public function testEquals($literal1, $literal2, $expectedResult): void
    {
        $this->assertSame($expectedResult, $literal1->equals($literal2));
        $this->assertSame($expectedResult, $literal2->equals($literal1));
    }

    public function equalsProvider(): array
    {
        return [
            [
                new ConstructedLiteral(
                    [
                        new IntegerLiteral(42),
                        new DigitStringLiteral('01234')
                    ]
                ),
                new ConstructedLiteral(
                    [
                        new NonNegativeIntegerLiteral(42),
                        new StringLiteral('01234')
                    ]
                ),
                true
            ],
            [
                new ConstructedLiteral([ new IntegerLiteral(42) ]),
                new ConstructedLiteral([ new NonNegativeIntegerLiteral(42) ]),
                true
            ],
            [
                new ConstructedLiteral([ new IntegerLiteral(42) ]),
                new ConstructedLiteral(
                    [
                        new IntegerLiteral(42),
                        new StringLiteral('foo')
                    ]
                ),
                false
            ],
            [
                new ConstructedLiteral(
                    [
                        new IntegerLiteral(42),
                        new StringLiteral('foo')
                    ]
                ),
                new ConstructedLiteral(
                    [
                        new IntegerLiteral(42),
                        new StringLiteral('bar')
                    ]
                ),
                false
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
