<?php

namespace alcamo\data_element;

use alcamo\dom\schema\SchemaFactory;
use alcamo\exception\DataValidationFailed;
use alcamo\rdf_literal\{
    BooleanLiteral,
    ConstructedStringLiteral,
    IntegerLiteral,
    StringLiteral
};
use PHPUnit\Framework\TestCase;

class ConstructedDeInstanceTest extends TestCase
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

        $literal = new ConstructedStringLiteral([ $literal1, $literal2 ]);

        $deInstance = new DeInstance($dataElement, $literal);

        $this->assertSame(2, count($deInstance->getChildren()));

        $this->assertEquals(
            $dataElement1,
            $deInstance->getChildren()['i']->getDataElement()
        );

        $this->assertEquals(
            $dataElement2,
            $deInstance->getChildren()['s']->getDataElement()
        );

        $this->assertEquals(
            $literal1,
            $deInstance->getChildren()['i']->getLiteral()
        );

        $this->assertEquals(
            $literal2,
            $deInstance->getChildren()['s']->getLiteral()
        );
    }

    public function testGetChildrenException(): void
    {
        $schema = (new SchemaFactory())->getMainSchema();

        $deInstance = new DeInstance(
            new ConstructedDataElement(
                [
                    new DataElement(
                        $schema->getGlobalType(self::XSD_NS . ' boolean')
                    )
                ]
            ),
            new ConstructedStringLiteral(
                [
                    new BooleanLiteral(true),
                    new IntegerLiteral(7)
                ]
            )
        );

        $this->expectException(DataValidationFailed::class);
        $this->expectExceptionMessage(
            'Validation failed; literal count 2 does not match '
                . 'data element count 1'
        );

        $deInstance->getChildren();
    }
}
