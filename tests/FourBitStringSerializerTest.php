<?php

namespace alcamo\data_element;

use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\FourBitStringLiteral;
use alcamo\uri\Uri;
use PHPUnit\Framework\TestCase;

class FourBitStringSerializerTest extends TestCase
{
    /**
     * @dataProvider serializeProvider
     */
    public function testSerialize(
        $minLength,
        $maxLength,
        $encoding,
        $literal,
        $expectedOutput,
        $expectedDeserialization
    ): void {
        $serializer = FourBitStringSerializer::newFromProps(
            (object)[
                'lengthRange' => new NonNegativeRange($minLength, $maxLength),
                'flags' => SerializerInterface::TRUNCATE_SILENTLY,
                'encoding' => $encoding
            ]
        );

        $datatype = $serializer->getDatatype();

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
                null,
                null,
                null,
                new FourBitStringLiteral(';1234=456<7:8>9?'),
                ';1234=456<7:8>9?',
                ';1234=456<7:8>9?'
            ],
            [
                5,
                null,
                'ASCII',
                new FourBitStringLiteral('42<<'),
                '42<< ',
                '42<<'
            ],
            [
                null,
                null,
                'FOUR-BIT',
                new FourBitStringLiteral('1=2'),
                "\x1D\x2F",
                '1=2?'
            ],
            [
                5,
                null,
                'FOUR-BIT',
                new FourBitStringLiteral('7==2'),
                "\x7D\xD2\xFF",
                '7==2??'
            ],
            [
                6,
                null,
                'FOUR-BIT',
                new FourBitStringLiteral('7==2'),
                "\x7D\xD2\xFF",
                '7==2??'
            ],
            /* The following changes the last character to a filler because
             * first is truncates to 3 digits and then add a filler nibble. */
            [
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
