<?php

namespace alcamo\data_element;

use alcamo\exception\{InvalidType, LengthOutOfRange};
use alcamo\range\NonNegativeRange;
use alcamo\rdfa\{NonNegativeIntegerLiteral, StringLiteral};
use PHPUnit\Framework\TestCase;

class StringSerializerTest extends TestCase
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
        $expectedOutput
    ): void {
        $datatype = AbstractSerializer::getSchemaFactory()
            ->getMainSchema()->getGlobalType($datatypeXName);

        $serializer = new StringSerializer(
            new DataElement($datatype),
            new NonNegativeRange($minLength, $maxLength),
            SerializerInterface::TRUNCATE_SILENTLY,
            $encoding
        );

        $output = $serializer->serialize($literal);

        $this->assertSame($expectedOutput, $output);

        $literal2 = $serializer->deserialize($output);

        $this->assertInstanceOf(StringLiteral::class, $literal2);

        if ($maxLength !== 7) {
            $this->assertEquals($literal->getValue(), $literal2->getValue());
        } else {
            $this->assertEquals('consäte', $literal2->getValue());
        }

        $this->assertEquals($datatype->getUri(), $literal2->getDatatypeUri());
    }

    public function serializeProvider(): array
    {
        return [
            [
                self::XSD_NS . ' string',
                null,
                null,
                null,
                new StringLiteral('Lorem ipsum'),
                'Lorem ipsum'
            ],
            [
                self::XSD_NS . ' token',
                11,
                15,
                'ISO-8859-1',
                new StringLiteral('dolör sit'),
                "dol\xF6r sit  "
            ],
            [
                self::XSD_NS . ' NMTOKEN',
                null,
                7,
                'ISO-8859-1',
                new StringLiteral('consätetur'),
                "cons\xE4te"
            ]
        ];
    }

    public function testInvalidDataType(): void
    {
        $this->expectException(InvalidType::class);

        $this->expectExceptionMessage(
            'Invalid type <alcamo\xml\XName>"http://www.w3.org/2001/XMLSchema '
                . 'dura...", expected one of [["http://www.w3.org/2001/XMLSchema", ...]'
        );

        new StringSerializer(
            new DataElement(
                AbstractSerializer::getSchemaFactory()
                    ->getMainSchema()->getGlobalType(self::XSD_NS . ' duration')
            )
        );
    }

    public function testInvalidLiteralClassException(): void
    {
        $this->expectException(InvalidType::class);

        $this->expectExceptionMessage(
            'Invalid type "alcamo\rdfa\NonNegativeIntegerLiteral", expected '
                . 'one of ["alcamo\rdfa\LangStringLiteral", "alc...]'
        );

        (new StringSerializer())->serialize(new NonNegativeIntegerLiteral(42));
    }

    public function testOutputTooLongException(): void
    {
        $this->expectException(LengthOutOfRange::class);

        $this->expectExceptionMessage(
            'Length 5 of "elitr" out of range [0, 3]'
        );

        (new StringSerializer(null, new NonNegativeRange(0, 3)))
            ->serialize(new StringLiteral('elitr'));
    }

    public function testInputLengthWrongException(): void
    {
        $this->expectException(LengthOutOfRange::class);

        $this->expectExceptionMessage(
            'Length 3 of "sed" out of range [5, "∞"]'
        );

        (new StringSerializer(null, new NonNegativeRange(5, null)))
            ->deserialize('sed');
    }
}
