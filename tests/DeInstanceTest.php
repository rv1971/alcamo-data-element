<?php

namespace alcamo\data_element;

use alcamo\dom\schema\SchemaFactory;
use alcamo\rdf_literal\StringLiteral;
use PHPUnit\Framework\TestCase;

class DeInstanceTest extends TestCase
{
    public const XSD_NS = SchemaFactory::XSD_NS;

    private static $schema_;

    public static function setUpBeforeClass(): void
    {
        self::$schema_ = (new SchemaFactory())->getMainSchema();
    }

    public function testConstruct(): void
    {
        $dataElement = new DataElement(
            self::$schema_->getGlobalType(self::XSD_NS . ' string')
        );

        $literal = new StringLiteral('foo');

        $deInstance1 = new DeInstance($dataElement, $literal);

        $this->assertNotSame($dataElement, $deInstance1->getDataElement());

        $this->assertEquals($dataElement, $deInstance1->getDataElement());

        $this->assertNotSame($literal, $deInstance1->getLiteral());

        $this->assertEquals($literal, $deInstance1->getLiteral());

        $deInstance2 = clone $deInstance1;

        $this->assertNotSame(
            $deInstance1->getDataElement(),
            $deInstance2->getDataElement()
        );

        $this->assertEquals(
            $deInstance1->getDataElement(),
            $deInstance2->getDataElement()
        );

        $this->assertNotSame(
            $deInstance1->getLiteral(),
            $deInstance2->getLiteral(),
        );

        $this->assertEquals(
            $deInstance1->getLiteral(),
            $deInstance2->getLiteral(),
        );
    }
}
