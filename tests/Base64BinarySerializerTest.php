<?php

namespace alcamo\data_element;

use alcamo\binary_data\BinaryString;
use alcamo\range\NonNegativeRange;
use alcamo\rdfa\Base64BinaryLiteral;
use PHPUnit\Framework\TestCase;

class Base64BinarySerializerTest extends TestCase
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

        $serializer = new Base64BinarySerializer(
            new DataElement($datatype),
            new NonNegativeRange($minLength, $maxLength),
            SerializerInterface::TRUNCATE_SILENTLY
        );

        $output = $serializer->serialize($literal);

        $this->assertSame($expectedOutput, $output);

        $literal2 = $serializer->deserialize($output);

        $this->assertInstanceOf(Base64BinaryLiteral::class, $literal2);

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
                5,
                10,
                new Base64BinaryLiteral('Zm9v'),
                "foo\x00\x00",
                "foo\x00\x00"
            ],
            [
                self::XSD_NS . ' base64Binary',
                null,
                3,
                new Base64BinaryLiteral(new BinaryString('dolor')),
                "dol",
                "dol"
            ]
        ];
    }
}
