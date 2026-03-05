<?php

namespace alcamo\data_element;

use alcamo\exception\{InvalidType, OutOfRange};
use alcamo\range\NonNegativeRange;
use alcamo\rdfa\{
    DateLiteral,
    DateTimeLiteral,
    GDayLiteral,
    GMonthLiteral,
    GMonthDayLiteral,
    GYearMonthLiteral,
    PositiveGYearLiteral,
    TimeLiteral
};
use alcamo\xml\XName;
use PHPUnit\Framework\TestCase;

class DateTimeSerializerTest extends TestCase
{
    public const XSD_NS = SerializerInterface::XSD_NS;

    /**
     * @dataProvider serializeProvider
     */
    public function testSerialize(
        $datatypeXName,
        $format,
        $encoding,
        $literal,
        $expectedOutput,
        $expectedDeserialization
    ): void {
        $serializer = new DateTimeSerializer(
            $datatypeXName,
            $format,
            null,
            $encoding
        );

        $datatype = $serializer->getDatatype();

        if (isset($datatypeXName)) {
            $this->assertSame($datatypeXName, (string)$datatype->getXName());
        }

        $output = $serializer->serialize($literal);

        $this->assertSame($expectedOutput, $output);

        $literal2 = $serializer->deserialize($output);

        $this->assertInstanceOf(get_class($literal), $literal2);

        $this->assertTrue($expectedDeserialization->equals($literal2));

        $this->assertEquals($datatype->getUri(), $literal2->getDatatypeUri());
    }

    public function serializeProvider(): array
    {
        return [
            [
                self::XSD_NS . ' date',
                null,
                null,
                new DateLiteral('2020-02-25'),
                '2020-02-25',
                new DateLiteral('2020-02-25')
            ],
            [
                self::XSD_NS . ' dateTime',
                null,
                'BCD',
                new DateTimeLiteral('2026-02-26T17:22'),
                "\x20\x26\x02\x26\x17\x22\x00",
                new DateTimeLiteral('2026-02-26T17:22')
            ],
            [
                self::XSD_NS . ' gDay',
                null,
                'EBCDIC',
                new GDayLiteral(28),
                "\xF2\xF8",
                new GDayLiteral(28)
            ],
            [
                self::XSD_NS . ' gMonth',
                null,
                null,
                new GMonthLiteral(7),
                '07',
                new GMonthLiteral(7)
            ],
            [
                self::XSD_NS . ' gMonthDay',
                '00%d00%m',
                'BCD',
                new GMonthDayLiteral('05-31'),
                "\x00\x31\x00\x05",
                new GMonthDayLiteral('05-31')
            ],
            [
                self::XSD_NS . ' gYearMonth',
                '%y-%m',
                'EBCDIC',
                new GYearMonthLiteral('2006-08'),
                "\xF0\xF6\x60\xF0\xF8",
                new GYearMonthLiteral('2006-08')
                ],
            [
                PositiveGYearLiteral::DATATYPE_XNAME,
                '%y',
                'BCD',
                new PositiveGYearLiteral('2008'),
                "\x08",
                new PositiveGYearLiteral('2008')
            ],
            [
                self::XSD_NS . ' time',
                '%M%I',
                'BCD',
                new TimeLiteral('06:23-03:00'),
                "\x23\x06",
                new TimeLiteral('06:23')
            ]
        ];
    }

    public function testNegativeDateException(): void
    {
        $this->expectException(OutOfRange::class);

        $this->expectExceptionMessage(
            'Value "-0007" out of range [0, "∞"]'
        );

        (new DateTimeSerializer(null, null, null, 'BCD'))->serialize(
            new DateTimeLiteral((new \DateTime())->setDate(-7, 1, 2))
        );
    }

    public function testDatatypeMismatch(): void
    {
        $this->expectException(InvalidType::class);

        $this->expectExceptionMessage(
            'Invalid type <alcamo\xml\XName>"http://www.w3.org/2001/XMLSchema '
                . 'date...";  incompatible with serializer datatype '
                . 'http://www.w3.org/2001/XMLSchema date'
        );

        (new DateTimeSerializer(self::XSD_NS . ' date'))
            ->serialize(new DateTimeLiteral());
    }
}
