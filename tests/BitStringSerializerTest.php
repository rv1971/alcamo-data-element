<?php

namespace alcamo\data_element;

use alcamo\exception\{LengthOutOfRange, Unsupported};
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\BitStringLiteral;
use PHPUnit\Framework\TestCase;

class BitStringSerializerTest extends TestCase
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
        $serializer = BitStringSerializer::newFromProps(
            (object)[
                'lengthRange' => new NonNegativeRange($minLength, $maxLength),
                'flags' => $encoding == 'DUMP' || $encoding == 'X.690'
                    ? 0
                    : SerializerInterface::TRUNCATE_SILENTLY,
                'encoding' => $encoding
            ]
        );

        $datatype = $serializer->getDatatype();

        $output = $serializer->serialize($literal);

        $this->assertSame($expectedOutput, $output);

        $literal2 = $serializer->deserialize($output);

        $this->assertInstanceOf(BitStringLiteral::class, $literal2);

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
                new BitStringLiteral('001011'),
                '001011',
                '001011'
            ],
            [
                null,
                null,
                'BINARY',
                new BitStringLiteral('1'),
                "\x80",
                '10000000'
            ],
            [
                9,
                null,
                'BINARY',
                new BitStringLiteral('10101111'),
                "\xAF\x00",
                '1010111100000000'
            ],
            [
                null,
                5,
                'BINARY',
                new BitStringLiteral('11111111'),
                "\xF8",
                '11111000'
            ],
            [
                null,
                null,
                'X.690',
                new BitStringLiteral('1'),
                "\x07\x80",
                '1'
            ],
            [
                null,
                null,
                'X.690',
                new BitStringLiteral('00110011'),
                "\x00\x33",
                '00110011'
            ],
            [
                null,
                null,
                'DUMP',
                new BitStringLiteral('111'),
                '"111"',
                '111'
            ]
        ];
    }

    public function testConstructException(): void
    {
        $this->expectException(Unsupported::class);

        $this->expectExceptionMessage('truncation of X.690');

        BitStringSerializer::newFromProps(
            (object)[
                'flags' => BitStringSerializer::TRUNCATE_SILENTLY,
                'encoding' => 'X.690'
            ]
        );
    }

    public function testAdjustOutputLengthException(): void
    {
        $this->expectException(LengthOutOfRange::class);

        $this->expectExceptionMessage(
            'Length 2 of "\000\210" out of range [3, 3]'
        );

        BitStringSerializer::newFromProps(
            (object)[
                'lengthRange' => new NonNegativeRange(3, 3),
                'encoding' => 'X.690'
            ]
        )->serialize(new BitStringLiteral('10001000'));
    }
}
