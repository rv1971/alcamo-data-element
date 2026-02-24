<?php

namespace alcamo\data_element;

use alcamo\binary_data\BinaryString;
use alcamo\range\NonNegativeRange;
use alcamo\rdfa\HexBinaryLiteral;
use PHPUnit\Framework\TestCase;

class HexBinarySerializerTest extends TestCase
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
        $expectedOutput,
        $expectedDeserialization
    ): void {
        $datatype = AbstractSerializer::getSchemaFactory()
            ->getMainSchema()->getGlobalType($datatypeXName);

        $serializer = new HexBinarySerializer(
            new DataElement($datatype),
            new NonNegativeRange($minLength, $maxLength),
            SerializerInterface::TRUNCATE_SILENTLY
        );

        $output = $serializer->serialize($literal);

        $this->assertSame($expectedOutput, $output);

        $literal2 = $serializer->deserialize($output);

        $this->assertInstanceOf(HexBinaryLiteral::class, $literal2);

        $this->assertEquals(
            (new BinaryString($expectedDeserialization))->getData(),
            $literal2->getValue()
        );

        $this->assertEquals($datatype->getUri(), $literal2->getDatatypeUri());
    }

    public function serializeProvider(): array
    {
        return [
            [
                self::XSD_NS . ' hexBinary',
                null,
                null,
                new HexBinaryLiteral('DE45'),
                "\xDE\x45",
                'DE45'
            ],
            [
                self::XSD_NS . ' hexBinary',
                4,
                null,
                new HexBinaryLiteral('A1'),
                "\xA1\x00\x00\x00",
                'A1000000'
            ],
            [
                self::XSD_NS . ' hexBinary',
                2,
                3,
                new HexBinaryLiteral('A1A2A3A4'),
                "\xA1\xA2\xA3",
                'A1A2A3'
            ]
        ];
    }
}
