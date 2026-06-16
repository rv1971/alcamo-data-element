<?php

namespace alcamo\data_element;

use alcamo\dom\schema\{Schema, SchemaFactory};
use alcamo\exception\DataValidationFailed;
use alcamo\rdf_literal\{IntegerLiteral, LanguageLiteral, StringLiteral};
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class DeWorkbenchTest extends TestCase
{
    public function testCreation(): void
    {
        $deWorkbench = DeWorkbench::getMainInstance();

        $this->assertInstanceOf(
            Schema::class,
            $deWorkbench->getSchema()
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
            $deWorkbench->getSchema(),
            $deWorkbench->getLiteralFactory()->getSchema()
        );

        $this->assertSame(
            $deWorkbench->getSchema(),
            $deWorkbench->getLiteralTypeMap()->getSchema()
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

        $datatypeXName = Schema::XSD_NS . ' token';

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
                . 'different schemas'
        );

        DeWorkbench::newFromFactories(
            new LiteralFactory(),
            new LiteralTypeMap(
                (new SchemaFactory())->createFromUris(
                    [
                        (new FileUriFactory())
                            ->create(__DIR__ . DIRECTORY_SEPARATOR . 'bar.xsd')
                    ]
                )
            )
        );
    }

    public function testValidateDeInstance(): void
    {
        $deWorkbench = DeWorkbench::getMainInstance();

        $dataElement = new DataElement(
            $deWorkbench->getSchema()->createTypeFromUri(
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
            $deWorkbench->getSchema()->createTypeFromUri(
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
