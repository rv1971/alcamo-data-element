<?php

namespace alcamo\data_element;

use alcamo\dom\schema\SchemaFactory;
use alcamo\exception\DataValidationFailed;
use alcamo\rdf_literal\{IntegerLiteral, LanguageLiteral, StringLiteral};
use PHPUnit\Framework\TestCase;

class LiteralWorkbenchTest extends TestCase
{
    public function testCreation(): void
    {
        $literalWorkbench = LiteralWorkbench::getMainInstance();

        $this->assertInstanceOf(
            SchemaFactory::class,
            $literalWorkbench->getSchemaFactory()
        );

        $this->assertInstanceOf(
            LiteralFactory::class,
            $literalWorkbench->getLiteralFactory()
        );

        $this->assertInstanceOf(
            LiteralTypeMap::class,
            $literalWorkbench->getLiteralTypeMap()
        );

        $this->assertSame(
            $literalWorkbench->getSchemaFactory(),
            $literalWorkbench->getLiteralFactory()->getSchemaFactory()
        );

        $this->assertSame(
            $literalWorkbench->getSchemaFactory(),
            $literalWorkbench->getLiteralTypeMap()->getSchemaFactory()
        );

        $literalWorkbench2 = LiteralWorkbench::newFromFactories(
            $literalWorkbench->getLiteralFactory(),
            $literalWorkbench->getLiteralTypeMap()
        );

        $this->assertSame(
            $literalWorkbench->getLiteralFactory(),
            $literalWorkbench2->getLiteralFactory()
        );

        $this->assertSame(
            $literalWorkbench->getLiteralTypeMap(),
            $literalWorkbench2->getLiteralTypeMap()
        );
    }

    public function testException(): void
    {
        $this->expectException(DataValidationFailed::class);

        $this->expectExceptionMessage(
            'Validation failed; Literal factory and literal type map have '
                . 'different schema factories'
        );

        LiteralWorkbench::newFromFactories(
            new LiteralFactory(new SchemaFactory()),
            new LiteralTypeMap(new SchemaFactory()),
        );
    }

    public function testValidateDataElementInstance(): void
    {
        $literalWorkbench = LiteralWorkbench::getMainInstance();

        $dataElement = new DataElement(
            $literalWorkbench->getSchemaFactory()->createTypeFromUri(
                StringLiteral::getClassDefaultDatatypeUri()
            )
        );

        $type = $literalWorkbench->validateDataElementInstance(
            new DataElementInstance(
                $dataElement,
                new LanguageLiteral('cr')
            )
        );

        $this->assertSame(
            $literalWorkbench->getSchemaFactory()->createTypeFromUri(
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

        $literalWorkbench->validateDataElementInstance(
            new DataElementInstance(
                $dataElement,
                new IntegerLiteral(42)
            )
        );
    }
}
