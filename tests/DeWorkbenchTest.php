<?php

namespace alcamo\data_element;

use alcamo\dom\schema\{Schema, SchemaFactory};
use alcamo\exception\DataValidationFailed;
use alcamo\rdf_literal\{IntegerLiteral, LanguageLiteral, StringLiteral};
use alcamo\rdf_literal_workbench\{LiteralFactory, LiteralTypeMap};
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class DeWorkbenchTest extends TestCase
{
    public function testCreation(): void
    {
        $deWorkbench = DeWorkbench::getMainInstance();

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
