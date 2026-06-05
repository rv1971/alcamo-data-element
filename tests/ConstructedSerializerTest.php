<?php

namespace alcamo\data_element;

use alcamo\exception\{DataValidationFailed, Eof, InvalidType, SyntaxError};
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\{
    ConstructedStringLiteral,
    HexBinaryLiteral,
    IntegerLiteral,
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
        $encoding,
        $lengthRange,
        $literalData,
        $expectedOutput,
        $expectedDeserializionDigest,
        $expectedDump
    ): void {
        $serializer = ConstructedSerializer::newFromProps(
            [
                'serializers' => $serializers,
                'separator' => $separator,
                'encoding' => $encoding,
                'lengthRange' => $lengthRange,
                'flags' => ConstructedSerializer::TRUNCATE_SILENTLY
            ]
        );

        $literal = new ConstructedStringLiteral($literalData);

        $output = $serializer->serialize($literal);

        $this->assertSame($expectedOutput, $output);

        $literal2 = $serializer->deserialize($output);

        $this->assertInstanceOf(get_class($literal), $literal2);

        $this->assertSame($expectedDeserializionDigest, $literal2->getDigest());

        $this->assertEquals(
            ConstructedStringLiteral::DEFAULT_DATATYPE_URI,
            $literal2->getDatatypeUri()
        );

        $this->assertSame($expectedDump, $serializer->dump($literal));

        $literal3 = $serializer->dedump($expectedDump);

        $this->assertInstanceOf(get_class($literal), $literal3);

        switch ($expectedDeserializionDigest) {
            case 'bar|foo':
                $this->assertSame(
                    $expectedDeserializionDigest,
                    $literal3->getDigest()
                );

                break;

            case '':
                $this->assertSame('3|ABCD', $literal3->getDigest());
                break;

            default:
                $this->assertSame(
                    $literal->getDigest(),
                    $literal3->getDigest()
                );
        }
    }

    public function serializeProvider(): array
    {
        $intS = new NonNegativeIntegerSerializer();
        $stringS = new StringSerializer();
        $stringS4 = StringSerializer::newFromProps([ 'lengthRange' => [ 4 ] ]);
        $bcdS = NonNegativeIntegerSerializer::newFromProps(
            [ 'encoding' =>  'BCD' ]
        );
        $bcdS3 = NonNegativeIntegerSerializer::newFromProps(
            [ 'encoding' =>  'BCD', 'lengthRange' => [ 3 ] ]
        );
        $binS = new BinarySerializer();

        return [
            [
                [ $intS, $stringS4, $intS ],
                ',',
                null,
                null,
                [
                    new NonNegativeIntegerLiteral(7),
                    new StringLiteral('foo'),
                    new NonNegativeIntegerLiteral(42)
                ],
                '7,foo ,42',
                '7|foo|42',
                '[7,"foo",42]'
            ],
            [
                [ $stringS, $intS, $stringS4, $intS ],
                '/',
                'TEXT',
                null,
                [
                    new StringLiteral('bar'),
                    new NonNegativeIntegerLiteral(0),
                    new StringLiteral('foo')
                ],
                'bar/0/foo ',
                'bar|0|foo',
                '["bar"/0/"foo"]'
                ],
            [
                [ $stringS4, $stringS4 ],
                null,
                'BINARY',
                [ 20 ],
                [
                    new StringLiteral('bar'),
                    new StringLiteral('foo'),
                    new NonNegativeIntegerLiteral(0),
                    new NonNegativeIntegerLiteral(7),
                ],
                "bar foo \x00\x00",
                'bar|foo',
                '[ "bar" "foo" ]'
            ],
            [
                [ $bcdS, $binS, $binS ],
                'FF',
                'BINARY',
                [ 10 ],
                [
                    new NonNegativeIntegerLiteral(12),
                    new HexBinaryLiteral('abcd'),
                ],
                "\x12\xFF\xAB\xCD\x00",
                '12|ABCD00',
                "[12FF'ABCD']"
            ],
            [
                [ $bcdS, $bcdS3, $binS, $binS ],
                null,
                'BINARY',
                null,
                [
                    new NonNegativeIntegerLiteral(7),
                    new NonNegativeIntegerLiteral(42),
                    new HexBinaryLiteral('ab')
                ],
                "\x70\x42\xAB",
                '7|42|AB',
                "[ 7 42 'AB' ]"
            ],
            [
                [ $bcdS, $bcdS, $binS, $binS ],
                'e',
                'BINARY',
                [ 8 ],
                [
                    new NonNegativeIntegerLiteral(3),
                    new NonNegativeIntegerLiteral(4),
                    new HexBinaryLiteral('ab'),
                ],
                "\x3E\x4E\xAB\x00",
                '3|4|AB00',
                "[3e4e'AB']"
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
            new ConstructedStringLiteral(
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
            'Failed to read 6 unit(s) from object "616263" at offset 6 '
                . 'for key 2'
        );

        (new ConstructedSerializer(
            [
                StringSerializer::newFromProps([ 'lengthRange' => [ 1, 1 ] ]),
                StringSerializer::newFromProps([ 'lengthRange' => [ 2, 3 ] ]),
                StringSerializer::newFromProps([ 'lengthRange' => [ 3 ] ])
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

    public function testDedumpException1(): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage(
            'Syntax error in "[42"; not surrounded by "[" and "]"'
        );

        (new ConstructedSerializer([ new StringSerializer() ]))->dedump('[42');
    }

    public function testDedumpException2(): void
    {

        $serializer = ConstructedSerializer::newFromProps(
            [
                'serializers' => [ new IntegerSerializer() ],
                'flags' => ConstructedSerializer::TRUNCATE_SILENTLY
            ]
        );

        $this->assertTrue(
            (new ConstructedStringLiteral([ new IntegerLiteral(42) ]))
                ->equals($serializer->dedump('[ 42 43 ]'))
        );

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage(
            'Syntax error in "[ 42 43 ]" at offset 5 ("43 ]"); '
                . 'spurious trailing data'
        );

        (new ConstructedSerializer([ new IntegerSerializer() ]))
            ->dedump('[ 42 43 ]');
    }

    public function testDedumpException3(): void
    {

        $serializer = ConstructedSerializer::newFromProps(
            [
                'serializers' => [
                    new StringSerializer(),
                    new IntegerSerializer()
                ],
                'flags' => ConstructedSerializer::TRUNCATE_SILENTLY
            ]
        );

        $this->assertTrue(
            (new ConstructedStringLiteral([ new StringLiteral('foo') ]))
                ->equals($serializer->dedump('[ "foo" ]'))
        );

        $this->expectException(Eof::class);
        $this->expectExceptionMessage(
            'Failed to read from "[ "foo" ]" at offset 8 for key 1'
        );

        ConstructedSerializer::newFromProps(
            [
                'serializers' => [
                    new StringSerializer(),
                    new IntegerSerializer()
                ]
            ]
        )
            ->dedump('[ "foo" ]');
    }
}
