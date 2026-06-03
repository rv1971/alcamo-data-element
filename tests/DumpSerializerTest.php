<?php

namespace alcamo\data_element;

use alcamo\exception\SyntaxError;
use alcamo\rdf_literal\{
    Base64BinaryLiteral,
    ConstructedLiteral,
    DateLiteral,
    DateTimeLiteral,
    HexBinaryLiteral,
    IntegerLiteral,
    StringLiteral
};
use PHPUnit\Framework\TestCase;

class DumpSerializerTest extends TestCase
{
    public const XSD_NS = DumpSerializer::XSD_NS;

    public function testBasics(): void
    {
        $serializer = DumpSerializer::newFromProps([ 'flags' => 42 ]);

        $this->assertSame(
            self::XSD_NS . ' anySimpleType',
            (string)$serializer->getDatatype()->getXName()
        );

        $this->assertNull($serializer->getLengthRange());

        $this->assertSame(42, $serializer->getFlags());
    }

    /**
     * @dataProvider deserializeProvider
     */
    public function testDeserialize(
        $separator,
        $datatypeXName,
        $text,
        $expectedLiteral,
        $expectedSerialization
    ): void {
        $serializer =
            DumpSerializer::newFromProps([ 'separator' => $separator ]);

        $this->assertSame($separator, $serializer->getSeparator());

        $deWorkbench = $serializer->getDeWorkbench();

        $datatype = isset($datatypeXName)
            ? $deWorkbench->getSchema()->getGlobalType($datatypeXName)
            : null;

        $literal = $serializer->deserialize($text, $datatype);

        $this->assertSame(get_class($expectedLiteral), get_class($literal));

        if ($literal instanceof ConstructedLiteral) {
            $this->assertSame(count($expectedLiteral), count($literal));

            $expectedLiteral->rewind();

            foreach ($literal as $item) {
                $expectedItem = $expectedLiteral->current();

                $this->assertSame(get_class($expectedItem), get_class($item));

                $this->assertTrue(
                    $item->equals($expectedItem)
                );

                $this->assertSame(
                    $deWorkbench->validateLiteral($expectedItem),
                    $deWorkbench->validateLiteral($item)
                );

                $expectedLiteral->next();
            }
        } else {
            $this->assertTrue(
                $literal->equals($expectedLiteral)
            );

            $this->assertSame(
                $deWorkbench->validateLiteral($expectedLiteral)->getXName(),
                $deWorkbench->validateLiteral($literal)->getXName()
            );

            $this->assertSame(
                $expectedSerialization,
                $serializer->serialize($literal)
            );
        }
    }

    public function deserializeProvider(): array
    {
        return [
            [
                null,
                self::XSD_NS . ' token',
                '"foo"',
                new StringLiteral('foo', self::XSD_NS . '#token'),
                '"foo"'
            ],
            [
                null,
                self::XSD_NS . ' base64Binary',
                "'fed987'",
                new Base64BinaryLiteral('/tmH'),
                "'FED987'"
            ],
            [
                null,
                self::XSD_NS . ' short',
                '42',
                new IntegerLiteral(42, self::XSD_NS . '#short'),
                '42'
            ],
            [
                null,
                self::XSD_NS . ' date',
                '2026-04-30',
                new DateLiteral('2026-04-30'),
                '2026-04-30'
            ],
            [
                null,
                null,
                "[\r-43  \"bar  baz\" 'FF00'\t \"'qux'\"\n 2026-04-30T13:36:00 ]",
                new ConstructedLiteral(
                    [
                        new IntegerLiteral(-43,),
                        new StringLiteral('bar  baz'),
                        new HexBinaryLiteral('FF00'),
                        new StringLiteral("'qux'"),
                        new DateTimeLiteral('2026-04-30T13:36:00')
                    ]
                ),
                "[ -43 \"bar  baz\" 'FF00' \"'qux'\" 2026-04-30T13:36:00 ]",
            ],
            [
                '|',
                null,
                "[\"foo\"|41|'AABBCC']",
                new ConstructedLiteral(
                    [
                        new StringLiteral('foo'),
                        new IntegerLiteral(41),
                        new HexBinaryLiteral('AABBCC'),
                    ]
                ),
                "[\"foo\"|41|'AABBCC']"
            ],
            [
                null,
                null,
                '7*"@"',
                new StringLiteral('@@@@@@@'),
                '7 * "@"'
            ],
            [
                null,
                null,
                "2  *   'FE98'",
                new HexBinaryLiteral('FE98FE98'),
                "'FE98FE98'"
            ]
        ];
    }

    public function testDeserializeException(): void
    {
        $serializer = new DumpSerializer();

        $this->expectException(SyntaxError::class);

        $this->expectExceptionMessage(
            'Syntax error in "[ 42 "; not terminated by "]"'
        );

        $serializer->deserialize('[ 42 ');
    }
}
