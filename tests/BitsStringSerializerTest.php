<?php

namespace alcamo\data_element;

use alcamo\exception\{LengthOutOfRange, Unsupported};
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
                'flags' => $encoding == 'X.690'
                    ? 0
                    : SerializerInterface::TRUNCATE_SILENTLY,
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
            ],
            [
                null,
                null,
                'X.690',
                new BitsStringLiteral('1'),
                "\x07\x80",
                '1'
            ],
            [
                null,
                null,
                'X.690',
                new BitsStringLiteral('00110011'),
                "\x00\x33",
                '00110011'
            ]
        ];
    }

    public function testConstructException(): void
    {
        $this->expectException(Unsupported::class);

        $this->expectExceptionMessage('truncation of X.690');

        BitsStringSerializer::newFromProps(
            (object)[
                'flags' => BitsStringSerializer::TRUNCATE_SILENTLY,
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

        BitsStringSerializer::newFromProps(
            (object)[
                'lengthRange' => new NonNegativeRange(3, 3),
                'encoding' => 'X.690'
            ]
        )->serialize(new BitsStringLiteral('10001000'));
    }
}
