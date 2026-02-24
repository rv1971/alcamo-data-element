<?php

namespace alcamo\data_element;

use alcamo\range\NonNegativeRange;
use alcamo\rdfa\FourBitStringLiteral;
use alcamo\uri\Uri;
use PHPUnit\Framework\TestCase;

class FourBitStringSerializerTest extends TestCase
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
        $datatype = AbstractSerializer::getSchemaFactory()
            ->createTypeFromUri(FourBitStringLiteral::DATATYPE_URI);

        $serializer = new FourBitStringSerializer(
            new DataElement($datatype),
            new NonNegativeRange($minLength, $maxLength),
            SerializerInterface::TRUNCATE_SILENTLY,
            $encoding
        );

        $output = $serializer->serialize($literal);

        $this->assertSame($expectedOutput, $output);

        $literal2 = $serializer->deserialize($output);

        $this->assertInstanceOf(FourBitStringLiteral::class, $literal2);

        $this->assertEquals($expectedDeserialization, $literal2->getValue());

        $this->assertEquals($datatype->getUri(), $literal2->getDatatypeUri());
    }

    public function serializeProvider(): array
    {
        return [
            [
                FourBitStringLiteral::DATATYPE_XNAME,
                null,
                null,
                null,
                new FourBitStringLiteral(';1234=456<7:8>9?'),
                ';1234=456<7:8>9?',
                ';1234=456<7:8>9?'
            ],
            [
                FourBitStringLiteral::DATATYPE_URI,
                5,
                null,
                'ASCII',
                new FourBitStringLiteral('42<<'),
                '42<< ',
                '42<<'
            ],
            [
                FourBitStringLiteral::DATATYPE_URI,
                null,
                null,
                'FOUR-BIT',
                new FourBitStringLiteral('1=2'),
                "\x1D\x2F",
                '1=2?'
            ],
            [
                FourBitStringLiteral::DATATYPE_URI,
                5,
                null,
                'FOUR-BIT',
                new FourBitStringLiteral('7==2'),
                "\x7D\xD2\xFF",
                '7==2??'
            ],
            [
                FourBitStringLiteral::DATATYPE_URI,
                6,
                null,
                'FOUR-BIT',
                new FourBitStringLiteral('7==2'),
                "\x7D\xD2\xFF",
                '7==2??'
            ],
            [
                FourBitStringLiteral::DATATYPE_URI,
                2,
                3,
                'FOUR-BIT',
                new FourBitStringLiteral(':2<>'),
                "\xA2\xCF",
                ':2<?'
            ]
        ];
    }
}
