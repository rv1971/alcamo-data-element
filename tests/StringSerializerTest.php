<?php

namespace alcamo\data_element;

use alcamo\exception\{InvalidType, LengthOutOfRange, SyntaxError, Unsupported};
use alcamo\input_stream\StringInputStream;
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\{QNameLiteral, StringLiteral};
use PHPUnit\Framework\TestCase;

class StringSerializerTest extends TestCase
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
        $expectedDump
    ): void {
        $serializer = StringSerializer::newFromProps(
            (object)[
                'datatypeXName' => $datatypeXName,
                'lengthRange' => new NonNegativeRange($minLength, $maxLength),
                'flags' => SerializerInterface::TRUNCATE_SILENTLY,
                'encoding' => $encoding
            ]
        );

        $datatype = $serializer->getDatatype();

        $this->assertSame($datatypeXName, (string)$datatype->getXName());

        $output = $serializer->serialize($literal, $length);

        $this->assertSame($expectedOutput, $output);

        $hexOutput = $serializer->serializeToHex($literal, $length);

        $this->assertSame($expectedOutput, hex2bin($hexOutput));

        $literal2 = $serializer->deserialize($output);

        $this->assertInstanceOf(StringLiteral::class, $literal2);

        if ($maxLength !== 7) {
            $this->assertEquals($literal->getValue(), $literal2->getValue());
        } else {
            $this->assertEquals('consäte', $literal2->getValue());
        }

        $this->assertEquals($datatype->getUri(), $literal2->getDatatypeUri());

        $literal3 = $serializer->deserializeFromHex(bin2hex($output));

        $this->assertInstanceOf(StringLiteral::class, $literal3);

        if ($maxLength !== 7) {
            $this->assertEquals($literal->getValue(), $literal3->getValue());
        } else {
            $this->assertEquals('consäte', $literal3->getValue());
        }

        $this->assertEquals($datatype->getUri(), $literal3->getDatatypeUri());

        $dump = $serializer->dump($literal);

        $this->assertEquals($expectedDump, $dump);

        $this->assertTrue($literal->equals($serializer->dedump($dump)));

        $this->assertTrue(
            $literal->equals(
                $serializer->dedumpFromStream(new StringInputStream($dump))
            )
        );
    }

    public function serializeProvider(): array
    {
        return [
            [
                self::XSD_NS . ' string',
                null,
                null,
                null,
                null,
                new StringLiteral('AAA'),
                'AAA',
                '"AAA"'
            ],
            [
                self::XSD_NS . ' string',
                null,
                null,
                null,
                null,
                new StringLiteral('BBBB'),
                'BBBB',
                '4 * "B"'
            ],
            [
                self::XSD_NS . ' string',
                null,
                null,
                null,
                null,
                new StringLiteral('CDCDCDCDCD'),
                'CDCDCDCDCD',
                '5 * "CD"'
            ],
            [
                self::XSD_NS . ' normalizedString',
                11,
                15,
                null,
                'ISO-8859-1',
                new StringLiteral('dolör sit', self::XSD_NS . '#token'),
                "dol\xF6r sit  ",
                '"dolör sit"'
            ],
            [
                self::XSD_NS . ' NMTOKEN',
                null,
                7,
                null,
                'ISO-8859-1',
                new StringLiteral('consätetur', self::XSD_NS . '#NMTOKEN'),
                "cons\xE4te",
                '"consätetur"'
            ],
            [
                self::XSD_NS . ' NMTOKEN',
                0,
                10,
                4,
                'EBCDIC',
                new StringLiteral('12', self::XSD_NS . '#NMTOKEN'),
                "\xF1\xF2\x40\x40",
                "'F1F2'"
            ]
        ];
    }

    public function testInvalidDataType(): void
    {
        $this->expectException(InvalidType::class);

        $this->expectExceptionMessage(
            'Invalid type "http://www.w3.org/2001/XMLSchema dura...", '
                . 'expected one of ["http://www.w3.org/2001/XMLSchema str...]'
        );

        new StringSerializer(self::XSD_NS . ' duration');
    }

    public function testInvalidLiteralClassException(): void
    {
        $this->expectException(InvalidType::class);

        $this->expectExceptionMessage(
            'Invalid type <alcamo\xml\XName>"http://www.w3.org/2001/XMLSchema '
                . 'QName";  incompatible with serializer datatype '
                . 'http://www.w3.org/2001/XMLSchema string'
        );

        (new StringSerializer())->serialize(new QNameLiteral('f:oo'));
    }

    public function testOutputTooLongException(): void
    {
        $this->expectException(LengthOutOfRange::class);

        $this->expectExceptionMessage(
            'Length 5 of "elitr" out of range [0, 3]'
        );

        (new StringSerializer(null, null, new NonNegativeRange(0, 3)))
            ->serialize(new StringLiteral('elitr'));
    }

    public function testInputLengthWrongException(): void
    {
        $this->expectException(LengthOutOfRange::class);

        $this->expectExceptionMessage(
            'Length 3 of "sed" out of range [5, "∞"]'
        );

        (new StringSerializer(null, null, new NonNegativeRange(5, null)))
            ->deserialize('sed');
    }

    public function testSerializeConversionException(): void
    {
        $this->expectException(Unsupported::class);

        $serializer = StringSerializer::newFromProps([ 'encoding' => 'FOO' ]);

        $this->expectExceptionMessage('"conversion to FOO" not supported');

        $serializer->serialize(new StringLiteral('bar'));
    }

    public function testDeserializeConversionException(): void
    {
        $this->expectException(Unsupported::class);

        $serializer = StringSerializer::newFromProps([ 'encoding' => 'FOO' ]);

        $this->expectExceptionMessage('"conversion from FOO" not supported');

        $serializer->deserialize('bar');
    }

    public function testDumpException(): void
    {
        $this->expectException(Unsupported::class);

        $this->expectExceptionMessage(
            '"dumping a literal containing a double..." not supported '
                . 'in "foo"bar"'
        );

        (new StringSerializer())->dump(new StringLiteral('foo"bar'));
    }

    public function testDedumpException(): void
    {
        $this->expectException(SyntaxError::class);

        $this->expectExceptionMessage(
            'Syntax error, expected one of """ in ""foo" at offset 4 ("")'
        );

        (new StringSerializer())->dedump('"foo');
    }
}
