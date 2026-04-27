<?php

namespace alcamo\data_element;

use alcamo\binary_data\BinaryString;
use alcamo\exception\SyntaxError;
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\{Base64BinaryLiteral, HexBinaryLiteral};
use PHPUnit\Framework\TestCase;

class BinarySerializerTest extends TestCase
{
    public const XSD_NS = SerializerInterface::XSD_NS;

    /**
     * @dataProvider serializeProvider
     */
    public function testSerialize(
        $datatypeXName,
        $encoding,
        $minLength,
        $maxLength,
        $literal,
        $expectedOutput,
        $expectedDeserialization
    ): void {
        $serializer = BinarySerializer::newFromProps(
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

        $this->assertSame($datatypeXName, (string)$datatype->getXName());

        $output = $serializer->serialize($literal);

        $this->assertSame($expectedOutput, $output);

        $literal2 = $serializer->deserialize($output);

        $this->assertInstanceOf(
            $serializer->getDatatype()->getXName()->getLocalName()
                == 'base64Binary'
            ? Base64BinaryLiteral::class
            : HexBinaryLiteral::class,
            $literal2
        );

        $this->assertEquals(
            $expectedDeserialization,
            $literal2->getValue()->getData()
        );

        $this->assertEquals($datatype->getUri(), $literal2->getDatatypeUri());
    }

    public function serializeProvider(): array
    {
        return [
            [
                self::XSD_NS . ' base64Binary',
                'BINARY',
                5,
                10,
                new Base64BinaryLiteral('Zm9v'),
                "foo\x00\x00",
                "foo\x00\x00"
            ],
            [
                self::XSD_NS . ' base64Binary',
                'BINARY',
                null,
                3,
                new Base64BinaryLiteral(new BinaryString('dolor')),
                "dol",
                "dol"
            ],
            [
                self::XSD_NS . ' hexBinary',
                'BINARY',
                null,
                null,
                new HexBinaryLiteral('DE45'),
                "\xDE\x45",
                "\xDE\x45"
            ],
            [
                self::XSD_NS . ' hexBinary',
                'BINARY',
                4,
                null,
                new HexBinaryLiteral('A1'),
                "\xA1\x00\x00\x00",
                "\xA1\x00\x00\x00"
            ],
            [
                self::XSD_NS . ' hexBinary',
                'BINARY',
                2,
                3,
                new HexBinaryLiteral('A1A2A3A4'),
                "\xA1\xA2\xA3",
                "\xA1\xA2\xA3"
            ],
            [
                self::XSD_NS . ' hexBinary',
                'DUMP',
                null,
                null,
                new HexBinaryLiteral('1234ABCD'),
                "'1234ABCD'",
                "\x12\x34\xAB\xCD"
            ]
        ];
    }

    public function testUndumpException(): void
    {
        $this->expectException(SyntaxError::class);

        $this->expectExceptionMessage(
            'Syntax error in "\'123X\'"'
        );

        IntegerSerializer::newFromProps([ 'encoding' => 'DUMP' ])
            ->deserialize("'123X'");
    }
}
