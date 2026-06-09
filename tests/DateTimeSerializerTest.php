<?php

namespace alcamo\data_element;

use alcamo\exception\{InvalidType, OutOfRange};
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\{
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
        $asUtc,
        $length,
        $encoding,
        $literal,
        $expectedOutput,
        $expectedHexOutput,
        $expectedDeserialization,
        $expectedDump
    ): void {
        $serializer = DateTimeSerializer::newFromProps(
            (object)[
                'datatypeXName' => $datatypeXName,
                'asUtc' => $asUtc,
                'posixFormat' => $format,
                'encoding' => $encoding,
                'flags' => DateTimeSerializer::SKIP_LENGTH_CHECK
            ]
        );

        if (isset($format)) {
            $this->assertSame($format, (string)$serializer->getPosixFormat());
        }

        $this->assertSame((bool)$asUtc, $serializer->getAsUtc());

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

        if (!$expectedDeserialization->equals($literal2)) {
            var_dump($expectedDeserialization, $literal2);
        }

        $this->assertTrue($expectedDeserialization->equals($literal2));

        $this->assertEquals($datatype->getUri(), $literal2->getDatatypeUri());

        $literal3 = $serializer->deserializeFromHex($hexOutput);

        $this->assertInstanceOf(get_class($literal), $literal3);

        $this->assertTrue($expectedDeserialization->equals($literal3));

        $this->assertEquals($datatype->getUri(), $literal3->getDatatypeUri());

        $dump = $serializer->dump($literal2);

        $this->assertEquals($expectedDump, $dump);

        $this->assertTrue($literal2->equals($serializer->dedump($dump)));
    }

    public function serializeProvider(): array
    {
        return [
            [
                self::XSD_NS . ' date',
                null,
                null,
                null,
                null,
                new DateLiteral('2020-02-25'),
                '2020-02-25',
                '323032302D30322D3235',
                new DateLiteral('2020-02-25'),
                '"2020-02-25"'
            ],
            [
                self::XSD_NS . ' dateTime',
                null,
                false,
                null,
                'BCD',
                new DateTimeLiteral('2026-02-26T17:22'),
                "\x20\x26\x02\x26\x17\x22\x00",
                '20260226172200',
                new DateTimeLiteral('2026-02-26T17:22'),
                '20260226172200'
            ],
            [
                self::XSD_NS . ' gDay',
                null,
                false,
                null,
                'EBCDIC',
                new GDayLiteral(28),
                "\xF2\xF8",
                'F2F8',
                new GDayLiteral(28),
                "'F2F8'"
            ],
            [
                self::XSD_NS . ' gMonth',
                null,
                false,
                null,
                null,
                new GMonthLiteral(7),
                '07',
                '3037',
                new GMonthLiteral(7),
                '"07"'
            ],
            [
                self::XSD_NS . ' gMonthDay',
                '00%d00%m',
                false,
                null,
                'BCD',
                new GMonthDayLiteral('05-31'),
                "\x00\x31\x00\x05",
                '00310005',
                new GMonthDayLiteral('05-31'),
                '00310005'
            ],
            [
                self::XSD_NS . ' gYearMonth',
                '%y-%m',
                false,
                6,
                'EBCDIC',
                new GYearMonthLiteral('2006-08'),
                "\xF0\xF6\x60\xF0\xF8\x40",
                'F0F660F0F840',
                new GYearMonthLiteral('2006-08'),
                "'F0F660F0F8'"
            ],
            [
                PositiveGYearLiteral::DEFAULT_DATATYPE_XNAME,
                '%y',
                false,
                null,
                'BCD',
                new PositiveGYearLiteral('2008'),
                "\x08",
                '08',
                new PositiveGYearLiteral('2008'),
                '08'
            ],
            [
                self::XSD_NS . ' time',
                '%M%I',
                true,
                6,
                'BCD',
                new TimeLiteral('06:23-03:00'),
                "\x00\x23\x09",
                '002309',
                new TimeLiteral('09:23'),
                '2309'
            ]
        ];
    }

    public function testNegativeDateException(): void
    {
        $this->expectException(OutOfRange::class);

        $this->expectExceptionMessage(
            'Value "-0007" out of range [0, "∞"]'
        );

        DateTimeSerializer::newFromProps((object)[ 'encoding' => 'BCD' ])
            ->serialize(
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
