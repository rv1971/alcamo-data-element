<?php

namespace alcamo\data_element;

use alcamo\dom\schema\SchemaFactory;
use alcamo\exception\InvalidType;
use PHPUnit\Framework\TestCase;

class ConstructedDataElementTest extends TestCase
{
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
