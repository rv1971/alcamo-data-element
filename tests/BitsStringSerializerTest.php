<?php

namespace alcamo\data_element;

use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\BitsStringLiteral;
use PHPUnit\Framework\TestCase;

class BitsStringSerializerTest extends TestCase
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
        $serializer = BitsStringSerializer::newFromProps(
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

        $this->assertInstanceOf(BitsStringLiteral::class, $literal2);

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
                new BitsStringLiteral('001011'),
                '001011',
                '001011'
            ],
            [
                null,
                null,
                'BINARY',
                new BitsStringLiteral('1'),
                "\x80",
                '10000000'
            ],
            [
                9,
                null,
                'BINARY',
                new BitsStringLiteral('10101111'),
                "\xAF\x00",
                '1010111100000000'
            ],
            [
                null,
                5,
                'BINARY',
                new BitsStringLiteral('11111111'),
                "\xF8",
                '11111000'
            ]
        ];
    }
}
