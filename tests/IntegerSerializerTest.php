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
        $encoding,
        $literal,
        $expectedOutput,
        $expectedDeserialization
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
                4,
                null,
                'EBCDIC',
                new GMonthLiteral(12),
                "\xF0\xF0\xF1\xF2",
                (new GMonthLiteral(12))->getValue()
            ],
            [
                self::XSD_NS . ' gYear',
                8,
                null,
                null,
                new GYearLiteral(-753),
                "-0000753",
                (new GYearLiteral(-753))->getValue()
            ],
            [
                self::XSD_NS . ' long',
                4,
                null,
                'BIG-ENDIAN',
                new IntegerLiteral(-3, self::XSD_NS . '#short'),
                "\xFF\xFF\xFF\xFD",
                -3
            ],
            [
                self::XSD_NS . ' short',
                3,
                null,
                'EBCDIC',
                new IntegerLiteral(-7, self::XSD_NS . '#byte'),
                "\x60\xF0\xF7",
                -7
            ],
            [
                null,
                null,
                null,
                'DUMP',
                new IntegerLiteral(1905),
                '1905',
                1905
            ]
        ];
    }

    public function testUndumpException(): void
    {
        $this->expectException(SyntaxError::class);

        $this->expectExceptionMessage(
            'Syntax error in "42x"'
        );

        IntegerSerializer::newFromProps([ 'encoding' => 'DUMP' ])
            ->deserialize('42x');
    }
}
