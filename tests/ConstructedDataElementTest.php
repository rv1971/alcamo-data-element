<?php

namespace alcamo\data_element;

use alcamo\dom\schema\SchemaFactory;
use alcamo\exception\InvalidType;
use PHPUnit\Framework\TestCase;

class ConstructedDataElementTest extends TestCase
{
    public const XSD_NS = SchemaFactory::XSD_NS;

    public function testBasics(): void
    {
        $schema = (new SchemaFactory())->getMainSchema();

        $dataElement1 = new DataElement(
            $schema->getGlobalType(self::XSD_NS . ' integer')
        );

        $dataElement2 = new DataElement(
            $schema->getGlobalType(self::XSD_NS . ' string')
        );

        $dataElement = new ConstructedDataElement(
            [ 'i' => $dataElement1, 's' => $dataElement2 ]
        );

        $this->assertSame($dataElement1, $dataElement['i']);
        $this->assertSame($dataElement2, $dataElement['s']);
    }

    public function testContructorException(): void
    {
        $schema = (new SchemaFactory())->getMainSchema();

        $this->expectException(InvalidType::class);
        $this->expectExceptionMessage(
            'Invalid type "string", expected one of '
                . '"alcamo\data_element\DataElementInterface"'
        );

        new ConstructedDataElement(
            [
                new DataElement(
                    $schema->getGlobalType(SchemaFactory::XSD_NS . ' boolean')
                ),
                'foo'
            ]
        );
    }
}
