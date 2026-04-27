<?php

namespace alcamo\data_element;

use alcamo\exception\{DataValidationFailed, Eof, InvalidType, SyntaxError};
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\{
    HexBinaryLiteral,
    NonNegativeIntegerLiteral,
    StringLiteral
};
use PHPUnit\Framework\TestCase;

class ConstructedSerializerTest extends TestCase
{
    public const XSD_NS = SerializerInterface::XSD_NS;

    /**
     * @dataProvider serializeProvider
     */
    public function testSerialize(
        $serializers,
        $separator,
        $lengthRange,
        $literalData,
        $expectedOutput,
        $expectedDeserializionDigest
    ): void {
        $serializer = ConstructedSerializer::newFromProps(
            [
                'serializers' => $serializers,
                'separator' => $separator,
                'lengthRange' => $lengthRange,
                'flags' => ConstructedSerializer::TRUNCATE_SILENTLY
            ]
        );

        $literal = new ConstructedLiteral($literalData);

        $output = $serializer->serialize($literal);

        $this->assertSame($expectedOutput, $output);

        $literal2 = $serializer->deserialize($output);

        $this->assertInstanceOf(get_class($literal), $literal2);

        $this->assertSame($expectedDeserializionDigest, $literal2->getDigest());

        $this->assertEquals(
            ConstructedLiteral::DEFAULT_DATATYPE_URI,
            $literal2->getDatatypeUri()
        );
    }

    public function serializeProvider(): array
    {
        $intS = new NonNegativeIntegerSerializer();
        $stringS = new StringSerializer();
        $stringS4 = StringSerializer::newFromProps(
            ['lengthRange' => new NonNegativeRange(4, null)]
        );
        $bcdS = NonNegativeIntegerSerializer::newFromProps(
            [ 'encoding' =>  'BCD']
        );
        $binS = new BinarySerializer();

        return [
            [
                [ $intS, $stringS4, $intS, $intS ],
                ',',
                null,
                [
                    new NonNegativeIntegerLiteral(7),
                    new StringLiteral('foo'),
                    null,
                    new NonNegativeIntegerLiteral(42)
                ],
                '7,foo ,,42',
                '7|foo||42'
            ],
            [
                [ $stringS, $stringS, $intS, $stringS4, $intS ],
                '/',
                null,
                [
                    null,
                    new StringLiteral('bar'),
                    new NonNegativeIntegerLiteral(0),
                    new StringLiteral('foo')
                ],
                '/bar/0/foo ',
                '|bar|0|foo'
            ],
            [
                [ $stringS4, $stringS4 ],
                null,
                new NonNegativeRange(10),
                [
                    new StringLiteral('bar'),
                    new StringLiteral('foo'),
                    new NonNegativeIntegerLiteral(0),
                    new NonNegativeIntegerLiteral(7),
                ],
                'bar foo   ',
                'bar|foo'
            ],
            [
                [ $bcdS, $binS, $binS ],
                "\xFF",
                new NonNegativeRange(5),
                [
                    new NonNegativeIntegerLiteral(3),
                    new HexBinaryLiteral('abcd'),
                ],
                "\x03\xFF\xAB\xCD\x00",
                '3|ABCD00'
            ]
        ];
    }

    public function testContructorException(): void
    {
        $this->expectException(InvalidType::class);
        $this->expectExceptionMessage(
            'Invalid type "alcamo\range\NonNegativeRange", expected one of '
                . '"alcamo\data_element\SerializerInterface"'
        );

        new ConstructedSerializer(
            [ new StringSerializer(), new NonNegativeRange() ]
        );
    }

    public function testSerializeLiteralTypeException(): void
    {
        $this->expectException(InvalidType::class);
        $this->expectExceptionMessage(
            'Invalid type "alcamo\rdf_literal\StringLiteral"; '
                . 'incompatible with '
                . 'alcamo\data_element\ConstructedSerializer'
        );

        (new ConstructedSerializer([ new StringSerializer() ]))
            ->serialize(new StringLiteral());
    }

    public function testLiteralCountWrongException(): void
    {
        $this->expectException(DataValidationFailed::class);
        $this->expectExceptionMessage(
            'Validation failed; literal count 2 does not match '
                . 'serializer count 1'
        );

        (new ConstructedSerializer([ new StringSerializer() ]))->serialize(
            new ConstructedLiteral(
                [ new StringLiteral(), new StringLiteral() ]
            )
        );
    }

    public function testDeserializeException1(): void
    {
        $this->expectException(Eof::class);
        $this->expectExceptionMessage(
            'Failed to read from "a+b" at offset 4 for key 2'
        );

        (new ConstructedSerializer(
            [
                new StringSerializer(),
                new StringSerializer(),
                new StringSerializer()
            ],
            '+'
        ))->deserialize('a+b');
    }

    public function testDeserializeException2(): void
    {
        $this->expectException(Eof::class);
        $this->expectExceptionMessage(
            'Failed to read 3 unit(s) from object "abc" at offset 3 '
                . 'for key 2'
        );

        (new ConstructedSerializer(
            [
                StringSerializer::newFromProps(
                    [ 'lengthRange' => new NonNegativeRange(1, 1) ]
                ),
                StringSerializer::newFromProps(
                    [ 'lengthRange' => new NonNegativeRange(2, 3) ]
                ),
                StringSerializer::newFromProps(
                    [ 'lengthRange' => new NonNegativeRange(3, null) ]
                )
            ],
            null
        ))->deserialize('abc');
    }

    public function testDeserializeException3(): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage(
            'Syntax error in "foo|bar" at offset 4 ("bar"); '
                . 'spurious trailing data'
        );

        (new ConstructedSerializer([ new StringSerializer() ], '|'))
            ->deserialize('foo|bar');
    }
}
