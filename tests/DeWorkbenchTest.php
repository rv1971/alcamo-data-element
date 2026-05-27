<?php

namespace alcamo\data_element;

use alcamo\dom\schema\SchemaFactory;
use alcamo\exception\DataValidationFailed;
use alcamo\rdf_literal\{IntegerLiteral, LanguageLiteral, StringLiteral};
use PHPUnit\Framework\TestCase;

class DeWorkbenchTest extends TestCase
{
    public function testCreation(): void
    {
        $deWorkbench = DeWorkbench::getMainInstance();

        $this->assertInstanceOf(
            SchemaFactory::class,
            $deWorkbench->getSchemaFactory()
        );

        $this->assertInstanceOf(
            LiteralFactory::class,
            $deWorkbench->getLiteralFactory()
        );

        $this->assertInstanceOf(
            LiteralTypeMap::class,
            $deWorkbench->getLiteralTypeMap()
        );

        $this->assertSame(
            $deWorkbench->getSchemaFactory(),
            $deWorkbench->getLiteralFactory()->getSchemaFactory()
        );

        $this->assertSame(
            $deWorkbench->getSchemaFactory(),
            $deWorkbench->getLiteralTypeMap()->getSchemaFactory()
        );

        $deWorkbench2 = DeWorkbench::newFromFactories(
            $deWorkbench->getLiteralFactory(),
            $deWorkbench->getLiteralTypeMap()
        );

        $this->assertSame(
            $deWorkbench->getLiteralFactory(),
            $deWorkbench2->getLiteralFactory()
        );

        $this->assertSame(
            $deWorkbench->getLiteralTypeMap(),
            $deWorkbench2->getLiteralTypeMap()
        );

        $datatypeXName = SchemaFactory::XSD_NS . ' token';

        $rdfaData = [ [ 'rdfs:label', 'foo' ] ];

        $dataElement = $deWorkbench->createDataElementFromXName(
            $datatypeXName,
            $rdfaData
        );

        $this->assertSame(
            $datatypeXName,
            (string)$dataElement->getDatatype()->getXName()
        );

        $this->assertSame('foo', $dataElement->getLabel());
    }

    public function testException(): void
    {
        $this->expectException(DataValidationFailed::class);

        $this->expectExceptionMessage(
            'Validation failed; Literal factory and literal type map have '
                . 'different schema factories'
        );

        DeWorkbench::newFromFactories(
            new LiteralFactory(new SchemaFactory()),
            new LiteralTypeMap(new SchemaFactory()),
        );
    }

    public function testValidateDeInstance(): void
    {
        $deWorkbench = DeWorkbench::getMainInstance();

        $dataElement = new DataElement(
            $deWorkbench->getSchemaFactory()->createTypeFromUri(
                StringLiteral::getClassDefaultDatatypeUri()
            )
        );

        $type = $deWorkbench->validateDeInstance(
            new DeInstance(
                $dataElement,
                new LanguageLiteral('cr')
            )
        );

        $this->assertSame(
            $deWorkbench->getSchemaFactory()->createTypeFromUri(
                LanguageLiteral::getClassDefaultDatatypeUri()
            ),
            $type
        );

        $this->expectException(DataValidationFailed::class);

        $this->expectExceptionMessage(
            'Validation failed; literal datatype '
                . 'http://www.w3.org/2001/XMLSchema integer not derived '
                . 'from data element datatype '
                . 'http://www.w3.org/2001/XMLSchema string'
        );

        $deWorkbench->validateDeInstance(
            new DeInstance(
                $dataElement,
                new IntegerLiteral(42)
            )
        );
    }
}
