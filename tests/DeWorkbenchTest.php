<?php

namespace alcamo\data_element;

use alcamo\dom\schema\{Schema, SchemaFactory};
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\exception\DataValidationFailed;
use alcamo\rdf_literal\{
    ConstructedStringLiteral,
    DurationLiteral,
    IntegerLiteral,
    LanguageLiteral,
    StringLiteral
};
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

    public function testValidateDeInstanceException(): void
    {
        $deWorkbench = DeWorkbench::getMainInstance();

        $schema = $deWorkbench->getSchema();

        $dataElement1 = new DataElement(
            $schema->getGlobalType(Schema::XSD_NS . ' integer')
        );

        $dataElement2 = new DataElement(
            $schema->getGlobalType(Schema::XSD_NS . ' string')
        );

        $dataElement = new ConstructedDataElement(
            [ $dataElement1, $dataElement2 ]
        );

        $literal1 = new IntegerLiteral(42);
        $literal2 = new StringLiteral('bar');
        $literal3 = new DurationLiteral('P1Y');

        $validDeInstance = new ConstructedDeInstance(
            $dataElement,
            new ConstructedStringLiteral([ $literal1, $literal2 ])
        );

        $this->assertInstanceOf(
            SimpleTypeInterface::class,
            $deWorkbench->validateDeInstance($validDeInstance)
        );

        $invalidDeInstance = new ConstructedDeInstance(
            $dataElement,
            new ConstructedStringLiteral([ $literal1, $literal3 ])
        );

        $this->expectException(DataValidationFailed::class);

        $this->expectExceptionMessage(
            'Validation failed; literal datatype '
                . 'http://www.w3.org/2001/XMLSchema duration not derived '
                . 'from data element datatype '
                . 'http://www.w3.org/2001/XMLSchema string'
        );

        $deWorkbench->validateDeInstance($invalidDeInstance);
    }
}
