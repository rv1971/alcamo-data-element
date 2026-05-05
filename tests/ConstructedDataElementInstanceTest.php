<?php

namespace alcamo\data_element;

use alcamo\dom\schema\SchemaFactory;
use alcamo\exception\DataValidationFailed;
use alcamo\rdf_literal\{
    BooleanLiteral,
    ConstructedLiteral,
    IntegerLiteral,
    StringLiteral
};
use PHPUnit\Framework\TestCase;

class ConstructedDataElementInstanceTest extends TestCase
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

        $literal1 = new IntegerLiteral(42);
        $literal2 = new StringLiteral('foo');

        $literal = new ConstructedLiteral([ $literal1, $literal2 ]);

        $dataElementInstance = new ConstructedDataElementInstance(
            $dataElement,
            $literal
        );

        $this->assertSame(2, count($dataElementInstance));

        $this->assertSame(
            $dataElement1,
            $dataElementInstance['i']->getDataElement()
        );

        $this->assertSame(
            $dataElement2,
            $dataElementInstance['s']->getDataElement()
        );

        $this->assertSame($literal1, $dataElementInstance['i']->getLiteral());

        $this->assertSame($literal2, $dataElementInstance['s']->getLiteral());
    }

    public function testContructorException(): void
    {
        $schema = (new SchemaFactory())->getMainSchema();

        $this->expectException(DataValidationFailed::class);
        $this->expectExceptionMessage(
            'Validation failed; literal count 2 does not match '
                . 'data element count 1'
        );

        new ConstructedDataElementInstance(
            new ConstructedDataElement(
                [
                    new DataElement(
                        $schema->getGlobalType(self::XSD_NS . ' boolean')
                    )
                ]
            ),
            new ConstructedLiteral(
                [
                    new BooleanLiteral(true),
                    new IntegerLiteral(7)
                ]
            )
        );
    }
}
