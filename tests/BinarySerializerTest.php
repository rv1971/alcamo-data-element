<?php

namespace alcamo\data_element;

use alcamo\binary_data\BinaryString;
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
        $minLength,
        $maxLength,
        $literal,
        $expectedOutput
    ): void {
        $serializer = BinarySerializer::newFromProps(
            (object)[
                'datatypeXName' => $datatypeXName,
                'lengthRange' => new NonNegativeRange($minLength, $maxLength),
                'flags' => SerializerInterface::TRUNCATE_SILENTLY
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

        $this->assertEquals($output, $literal2->getValue()->getData());

        $this->assertEquals($datatype->getUri(), $literal2->getDatatypeUri());
    }

    public function serializeProvider(): array
    {
        return [
            [
                self::XSD_NS . ' base64Binary',
                5,
                10,
                new Base64BinaryLiteral('Zm9v'),
                "foo\x00\x00"
            ],
            [
                self::XSD_NS . ' base64Binary',
                null,
                3,
                new Base64BinaryLiteral(new BinaryString('dolor')),
                "dol"
            ],
            [
                self::XSD_NS . ' hexBinary',
                null,
                null,
                new HexBinaryLiteral('DE45'),
                "\xDE\x45"
            ],
            [
                self::XSD_NS . ' hexBinary',
                4,
                null,
                new HexBinaryLiteral('A1'),
                "\xA1\x00\x00\x00"
            ],
            [
                self::XSD_NS . ' hexBinary',
                2,
                3,
                new HexBinaryLiteral('A1A2A3A4'),
                "\xA1\xA2\xA3"
            ]
        ];
    }
}
