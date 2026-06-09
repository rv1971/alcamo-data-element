<?php

namespace alcamo\data_element;

use alcamo\exception\SyntaxError;
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
        $length,
        $encoding,
        $literal,
        $expectedOutput,
        $expectedHexOutput,
        $expectedDeserialization,
        $expectedDump
    ): void {
        $serializer = NonNegativeIntegerSerializer::newFromProps(
            (object)[
                'datatypeXName' => $datatypeXName,
                'lengthRange' => new NonNegativeRange($minLength, $maxLength),
                'flags' => SerializerInterface::TRUNCATE_SILENTLY,
                'encoding' => $encoding
            ]
        );

        $datatype = $serializer->getDatatype();

        if (isset($datatypeXName)) {
            $this->assertSame($datatypeXName, (string)$datatype->getXName());
        }

        $output = $serializer->serialize($literal, $length);

        $this->assertSame($expectedOutput, $output);

        $hexOutput = $serializer->serializeToHex($literal, $length);

        $this->assertSame($expectedHexOutput, $hexOutput);

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

        $literal3 = $serializer->deserializeFromHex($hexOutput);

        $this->assertInstanceOf(get_class($literal), $literal3);

        if ($expectedDeserialization instanceof \DateTimeInterface) {
            $diff = new Duration(
                $expectedDeserialization->diff($literal3->getValue(), true)
            );

            $this->assertTrue($diff->getTotalSeconds() < 5);
        } else {
            $this->assertEquals(
                $expectedDeserialization,
                $literal3->getValue()
            );
        }

        $this->assertEquals($datatype->getUri(), $literal3->getDatatypeUri());

        $dump = $serializer->dump($literal);

        $this->assertEquals($expectedDump, $dump);

        $this->assertTrue($literal->equals($serializer->dedump($dump)));
    }

    public function serializeProvider(): array
    {
        return [
            [
                self::XSD_NS . ' boolean',
                null,
                null,
                null,
                null,
                new BooleanLiteral(false),
                '0',
                '30',
                false,
                '"0"'
            ],
            [
                self::XSD_NS . ' boolean',
                null,
                null,
                null,
                'BCD',
                new BooleanLiteral(true),
                "\x01",
                '1',
                true,
                '1'
            ],
            [
                self::XSD_NS . ' gDay',
                null,
                null,
                null,
                'BIG-ENDIAN',
                new GDayLiteral(24),
                "\x18",
                '18',
                (new GDayLiteral(24))->getValue(),
                "'18'"
            ],
            [
                self::XSD_NS . ' gMonth',
                null,
                null,
                null,
                'EBCDIC',
                new GMonthLiteral(12),
                "\xF1\xF2",
                'F1F2',
                (new GMonthLiteral(12))->getValue(),
                "'F1F2'"
            ],
            [
                PositiveGYearLiteral::DEFAULT_DATATYPE_XNAME,
                8,
                null,
                null,
                null,
                new PositiveGYearLiteral(1975),
                "00001975",
                "3030303031393735",
                (new PositiveGYearLiteral(1975))->getValue(),
                '"1975"'
            ],
            [
                self::XSD_NS . ' unsignedLong',
                5,
                null,
                null,
                'BCD',
                new NonNegativeIntegerLiteral(
                    42,
                    self::XSD_NS . '#unsignedShort'
                ),
                "\x00\x00\x42",
                '00042',
                42,
                "42"
            ],
            [
                self::XSD_NS . ' nonNegativeInteger',
                5,
                null,
                null,
                'BIG-ENDIAN',
                new NonNegativeIntegerLiteral(1027),
                "\x00\x00\x00\x04\x03",
                '0000000403',
                1027,
                "'0403'"
            ],
            [
                self::XSD_NS . ' unsignedShort',
                2,
                null,
                null,
                'EBCDIC',
                new NonNegativeIntegerLiteral(
                    7,
                    self::XSD_NS . '#unsignedByte'
                ),
                "\xF0\xF7",
                'F0F7',
                7,
                "'F7'"
            ],
            [
                self::XSD_NS . ' nonNegativeInteger',
                null,
                2,
                null,
                'ASCII',
                new NonNegativeIntegerLiteral(123),
                "23",
                '3233',
                23,
                '"123"'
            ],
            [
                self::XSD_NS . ' unsignedByte',
                null,
                2,
                null,
                'BCD',
                new NonNegativeIntegerLiteral(
                    255,
                    self::XSD_NS . '#unsignedByte'
                ),
                "\x55",
                '55',
                55,
                '255'
            ],
            [
                self::XSD_NS . ' unsignedLong',
                null,
                3,
                null,
                'BCD',
                new NonNegativeIntegerLiteral(
                    1234,
                    self::XSD_NS . '#unsignedInt'
                ),
                "\x02\x34",
                '234',
                234,
                '1234'
            ],
            [
                null,
                null,
                null,
                2,
                'BIG-ENDIAN',
                new NonNegativeIntegerLiteral(0x12345),
                "\x23\x45",
                '2345',
                0x2345,
                "'012345'"
            ],
            [
                null,
                null,
                null,
                3,
                'EBCDIC',
                new NonNegativeIntegerLiteral(9876),
                "\xF8\xF7\xF6",
                'F8F7F6',
                876,
                "'F9F8F7F6'"
            ]
        ];
    }

    public function testInvalidPaddingException(): void
    {
        $serializer = NonNegativeIntegerSerializer::newFromProps(
            [
                'lengthRange' => new NonNegativeRange(0, 5),
                'encoding' => 'BCD'
            ]
        );

        $this->expectException(SyntaxError::class);

        $this->expectExceptionMessage(
            'Syntax error in "912345"; invalid left padding data "9"'
        );

        $serializer->deserializeFromHex('912345');
    }
}
