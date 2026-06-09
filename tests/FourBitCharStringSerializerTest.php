<?php

namespace alcamo\data_element;

use alcamo\exception\SyntaxError;
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\FourBitCharStringLiteral;
use alcamo\uri\Uri;
use PHPUnit\Framework\TestCase;

class FourBitCharStringSerializerTest extends TestCase
{
    /**
     * @dataProvider serializeProvider
     */
    public function testSerialize(
        $minLength,
        $maxLength,
        $length,
        $encoding,
        $literal,
        $expectedOutput,
        $expectedHexOutput,
        $expectedDeserialization,
        $expectedHexDeserialization,
        $expectedDump
    ): void {
        $serializer = FourBitCharStringSerializer::newFromProps(
            (object)[
                'lengthRange' => new NonNegativeRange($minLength, $maxLength),
                'flags' => SerializerInterface::TRUNCATE_SILENTLY,
                'encoding' => $encoding
            ]
        );

        $datatype = $serializer->getDatatype();

        $output = $serializer->serialize($literal, $length);

        $this->assertSame($expectedOutput, $output);

        $hexOutput = $serializer->serializeToHex($literal, $length);

        $this->assertSame($expectedHexOutput, $hexOutput);

        $literal2 = $serializer->deserialize($output);

        $this->assertInstanceOf(FourBitCharStringLiteral::class, $literal2);

        $this->assertEquals($expectedDeserialization, $literal2->getValue());

        $this->assertEquals($datatype->getUri(), $literal2->getDatatypeUri());

        $literal3 = $serializer->deserializeFromHex($hexOutput);

        $this->assertInstanceOf(FourBitCharStringLiteral::class, $literal3);

        $this->assertEquals($expectedHexDeserialization, $literal3->getValue());

        $this->assertEquals($datatype->getUri(), $literal3->getDatatypeUri());

        $dump = $serializer->dump($literal);

        $this->assertEquals($expectedDump, $dump);

        if ($dump != "'1D2F'") {
            $this->assertTrue($literal->equals($serializer->dedump($dump)));
        }
    }

    public function serializeProvider(): array
    {
        return [
            [
                null,
                null,
                null,
                null,
                new FourBitCharStringLiteral(';1234=456<7:8>9?'),
                ';1234=456<7:8>9?',
                '3B313233343D3435363C373A383E393F',
                ';1234=456<7:8>9?',
                ';1234=456<7:8>9?',
                '";1234=456<7:8>9?"'
            ],
            [
                5,
                null,
                null,
                'ASCII',
                new FourBitCharStringLiteral('42<<'),
                '42<< ',
                '34323C3C20',
                '42<<',
                '42<<',
                '"42<<"'
            ],
            [
                5,
                null,
                7,
                'ASCII',
                new FourBitCharStringLiteral('42<<'),
                '42<<   ',
                '34323C3C202020',
                '42<<',
                '42<<',
                '"42<<"'
            ],
            [
                null,
                null,
                null,
                'FOUR-BIT',
                new FourBitCharStringLiteral('1=2'),
                "\x1D\x2F",
                '1D2',
                '1=2?',
                '1=2',
                "'1D2F'"
            ],
            [
                5,
                null,
                null,
                'FOUR-BIT',
                new FourBitCharStringLiteral('7==2'),
                "\x7D\xD2\xFF",
                '7DD2F',
                '7==2??',
                '7==2?',
                "'7DD2'"
            ],
            [
                null,
                10,
                6,
                'FOUR-BIT',
                new FourBitCharStringLiteral('7==2'),
                "\x7D\xD2\xFF",
                '7DD2FF',
                '7==2??',
                '7==2??',
                "'7DD2'"
            ],
            /* The following changes the last character to a filler because
             * first is truncates to 3 digits and then add a filler nibble. */
            [
                2,
                3,
                null,
                'FOUR-BIT',
                new FourBitCharStringLiteral(':2<>'),
                "\xA2\xCF",
                'A2C',
                ':2<',
                ':2<',
                "'A2CE'"
            ]
        ];
    }

    public function testInvalidPaddingException(): void
    {
        $serializer = FourBitCharStringSerializer::newFromProps(
            [
                'lengthRange' => new NonNegativeRange(0, 3),
                'encoding' => 'FOUR-BIT'
            ]
        );

        $this->expectException(SyntaxError::class);

        $this->expectExceptionMessage(
            'Syntax error in "ABC0"; invalid right padding data "0"'
        );

        $serializer->deserializeFromHex('ABC0');
    }
}
