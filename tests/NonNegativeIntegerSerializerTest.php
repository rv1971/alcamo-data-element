<?php

namespace alcamo\data_element;

use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\{
    BooleanLiteral,
    GDayLiteral,
    GMonthLiteral,
    NonNegativeIntegerLiteral,
    PositiveGYearLiteral
};
use alcamo\time\Duration;
use PHPUnit\Framework\TestCase;

class NonNegativeIntegerSerializerTest extends TestCase
{
    public const XSD_NS = SerializerInterface::XSD_NS;

    /**
     * @dataProvider serializeProvider
     */
    public function testSerialize(
        $datatypeXName,
        $minLength,
        $maxLength,
        $encoding,
        $literal,
        $expectedOutput,
        $expectedDeserialization
    ): void {
        $serializer = NonNegativeIntegerSerializer::newFromProps(
            (object)[
                'datatypeXName' => $datatypeXName,
                'lengthRange' => new NonNegativeRange($minLength, $maxLength),
                'flags' => $encoding == 'DUMP'
                    ? 0
                    : SerializerInterface::TRUNCATE_SILENTLY,
                'encoding' => $encoding
            ]
        );

        $datatype = $serializer->getDatatype();

        if (isset($datatypeXName)) {
            $this->assertSame($datatypeXName, (string)$datatype->getXName());
        }

        $output = $serializer->serialize($literal);

        $this->assertSame($expectedOutput, $output);

        $literal2 = $serializer->deserialize($output);

        $this->assertInstanceOf(get_class($literal), $literal2);

        if ($expectedDeserialization instanceof \DateTimeInterface) {
            $diff = new Duration(
                $expectedDeserialization->diff($literal2->getValue(), true)
            );

            $this->assertTrue($diff->getTotalSeconds() < 5);
        } else {
            $this->assertEquals(
                $expectedDeserialization,
                $literal2->getValue()
            );
        }

        $this->assertEquals($datatype->getUri(), $literal2->getDatatypeUri());
    }

    public function serializeProvider(): array
    {
        return [
            [
                self::XSD_NS . ' boolean',
                null,
                null,
                null,
                new BooleanLiteral(false),
                '0',
                false
            ],
            [
                self::XSD_NS . ' boolean',
                null,
                null,
                'BCD',
                new BooleanLiteral(true),
                "\x01",
                true
            ],
            [
                self::XSD_NS . ' gDay',
                null,
                null,
                'BIG-ENDIAN',
                new GDayLiteral(24),
                "\x18",
                (new GDayLiteral(24))->getValue()
            ],
            [
                self::XSD_NS . ' gMonth',
                null,
                null,
                'EBCDIC',
                new GMonthLiteral(12),
                "\xF1\xF2",
                (new GMonthLiteral(12))->getValue()
                ],
            [
                PositiveGYearLiteral::DEFAULT_DATATYPE_XNAME,
                8,
                null,
                null,
                new PositiveGYearLiteral(1975),
                "00001975",
                (new PositiveGYearLiteral(1975))->getValue()
            ],
            [
                self::XSD_NS . ' unsignedLong',
                5,
                null,
                'BCD',
                new NonNegativeIntegerLiteral(
                    42,
                    self::XSD_NS . '#unsignedShort'
                ),
                "\x00\x00\x42",
                42
            ],
            [
                self::XSD_NS . ' nonNegativeInteger',
                5,
                null,
                'BIG-ENDIAN',
                new NonNegativeIntegerLiteral(1027),
                "\x00\x00\x00\x04\x03",
                1027
            ],
            [
                self::XSD_NS . ' unsignedShort',
                2,
                null,
                'EBCDIC',
                new NonNegativeIntegerLiteral(
                    7,
                    self::XSD_NS . '#unsignedByte'
                ),
                "\xF0\xF7",
                7
            ],
            [
                self::XSD_NS . ' nonNegativeInteger',
                null,
                2,
                'ASCII',
                new NonNegativeIntegerLiteral(123),
                "23",
                23
                ],
            [
                self::XSD_NS . ' unsignedByte',
                null,
                2,
                'BCD',
                new NonNegativeIntegerLiteral(
                    255,
                    self::XSD_NS . '#unsignedByte'
                ),
                "\x55",
                55
            ],
            [
                self::XSD_NS . ' unsignedLong',
                null,
                3,
                'BCD',
                new NonNegativeIntegerLiteral(
                    1234,
                    self::XSD_NS . '#unsignedInt'
                ),
                "\x02\x34",
                234
            ],
            [
                null,
                null,
                2,
                'BIG-ENDIAN',
                new NonNegativeIntegerLiteral(0x12345),
                "\x23\x45",
                0x2345
            ],
            [
                null,
                null,
                3,
                'EBCDIC',
                new NonNegativeIntegerLiteral(9876),
                "\xF8\xF7\xF6",
                876
            ],
            [
                null,
                null,
                null,
                'DUMP',
                new NonNegativeIntegerLiteral(1226),
                "1226",
                1226
            ]
        ];
    }
}
