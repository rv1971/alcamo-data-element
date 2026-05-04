<?php

namespace alcamo\data_element;

use alcamo\exception\{InvalidEnumerator, LengthOutOfRange};
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\DigitStringLiteral;
use PHPUnit\Framework\TestCase;

class DigitStringSerializerTest extends TestCase
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
        $serializer = DigitStringSerializer::newFromProps(
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

        $this->assertInstanceOf(DigitStringLiteral::class, $literal2);

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
                new DigitStringLiteral('000123456789'),
                '000123456789',
                '000123456789'
            ],
            [
                5,
                null,
                'ASCII',
                new DigitStringLiteral('42'),
                '42   ',
                '42'
            ],
            [
                null,
                null,
                'COMPRESSED-BCD',
                new DigitStringLiteral('421'),
                "\x42\x1F",
                '421'
            ],
            [
                7,
                null,
                'COMPRESSED-BCD',
                new DigitStringLiteral('002026'),
                "\x00\x20\x26\xFF",
                '002026'
            ],
            [
                8,
                null,
                'COMPRESSED-BCD',
                new DigitStringLiteral('002026'),
                "\x00\x20\x26\xFF",
                '002026'
            ],
            [
                2,
                3,
                'COMPRESSED-BCD',
                new DigitStringLiteral('1234'),
                "\x12\x3F",
                '123'
            ],
            [
                3,
                3,
                'EBCDIC',
                new DigitStringLiteral('17'),
                "\xF1\xF7\x40",
                '17'
            ]
        ];
    }

    public function testUnsupportedEncodingException(): void
    {
        $this->expectException(InvalidEnumerator::class);

        $this->expectExceptionMessage(
            'Invalid value "BCD", expected one of '
                . '["ASCII", "COMPRESSED-BCD", "EBCDIC"]'
        );

        DigitStringSerializer::newFromProps(
            (object)[ 'encoding' =>  'BCD']
        );
    }

    public function testInputLengthWrong(): void
    {
        $this->expectException(LengthOutOfRange::class);

        $this->expectExceptionMessage(
            'Length 6 of "12345f" out of range [0, 4]'
        );

        DigitStringSerializer::newFromProps(
            (object)[
                'lengthRange' => new NonNegativeRange(null, 4),
                'encoding' =>  'COMPRESSED-BCD'
            ]
        )
            ->deserialize("\x12\x34\x5f");
    }
}
