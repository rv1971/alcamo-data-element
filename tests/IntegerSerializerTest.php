<?php

namespace alcamo\data_element;

use alcamo\exception\SyntaxError;
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\{
    BooleanLiteral,
    GDayLiteral,
    GMonthLiteral,
    IntegerLiteral,
    GYearLiteral
};
use alcamo\time\Duration;
use PHPUnit\Framework\TestCase;

class IntegerSerializerTest extends TestCase
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
        $expectedDeserialization,
        $expectedDump
    ): void {
        $serializer = IntegerSerializer::newFromProps(
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

        $output = $serializer->serialize($literal, $length);

        $this->assertSame($expectedOutput, $output);

        $hexOutput = $serializer->serializeToHex($literal, $length);

        $this->assertSame($expectedOutput, hex2bin($hexOutput));

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

        $literal3 = $serializer->deserializeFromHex(bin2hex($output));

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
                false,
                '"0"'
            ],
            [
                self::XSD_NS . ' gDay',
                null,
                null,
                null,
                'BIG-ENDIAN',
                new GDayLiteral(24),
                "\x18",
                (new GDayLiteral(24))->getValue(),
                "'18'"
            ],
            [
                self::XSD_NS . ' gMonth',
                4,
                null,
                null,
                'EBCDIC',
                new GMonthLiteral(12),
                "\xF0\xF0\xF1\xF2",
                (new GMonthLiteral(12))->getValue(),
                "'F1F2'"
            ],
            [
                self::XSD_NS . ' gYear',
                8,
                null,
                null,
                null,
                new GYearLiteral(-753),
                "-0000753",
                (new GYearLiteral(-753))->getValue(),
                '"-753"'
            ],
            [
                self::XSD_NS . ' long',
                4,
                null,
                null,
                'BIG-ENDIAN',
                new IntegerLiteral(-3, self::XSD_NS . '#short'),
                "\xFF\xFF\xFF\xFD",
                -3,
                "'FD'"
            ],
            [
                self::XSD_NS . ' short',
                3,
                null,
                null,
                'EBCDIC',
                new IntegerLiteral(-7, self::XSD_NS . '#byte'),
                "\x60\xF0\xF7",
                -7,
                "'60F7'"
            ],
            [
                self::XSD_NS . ' nonNegativeInteger',
                3,
                7,
                4,
                'BIG-ENDIAN',
                new IntegerLiteral(7, self::XSD_NS . '#unsignedShort'),
                "\x00\x00\x00\x07",
                7,
                "'07'"
            ]
        ];
    }

    public function testUndumpException(): void
    {
        $this->expectException(SyntaxError::class);

        $this->expectExceptionMessage(
            'Syntax error in "42x"'
        );

        (new IntegerSerializer())->dedump('"42x"');
    }
}
