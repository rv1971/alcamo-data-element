<?php

namespace alcamo\data_element;

use alcamo\dom\schema\{Schema, SchemaFactory};
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\exception\InvalidType;
use alcamo\rdf_literal\{
    AnyUriLiteral,
    BooleanLiteral,
    ConstructedHexBinaryLiteral,
    ConstructedStringLiteral,
    DurationLiteral,
    HexBinaryLiteral,
    IntegerLiteral,
    LanguageLiteral,
    NamespaceConstantsInterface,
    QNameLiteral,
    StringLiteral
};
use alcamo\rdf_literal_workbench\{LiteralFactory, LiteralTypeMap};
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class DeWorkbenchTest extends TestCase implements NamespaceConstantsInterface
{
    public function testCreation(): void
    {
        $deWorkbench = DeWorkbench::getMainInstance();

        $datatypeXName = self::XSD_NS . ' token';

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

        $this->expectException(InvalidType::class);

        $this->expectExceptionMessage(
            'Invalid type "http://www.w3.org/2001/XMLSchema integer", '
                . 'expected one of "derived from http://www.w3.org/2001/X..."'
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
            $schema->getGlobalType(self::XSD_NS . ' integer')
        );

        $dataElement2 = new DataElement(
            $schema->getGlobalType(self::XSD_NS . ' string')
        );

        $dataElement = new ConstructedDataElement(
            [ $dataElement1, $dataElement2 ]
        );

        $literal1 = new IntegerLiteral(42);
        $literal2 = new StringLiteral('bar');
        $literal3 = new DurationLiteral('P1Y');

        $validDeInstance = new DeInstance(
            $dataElement,
            new ConstructedStringLiteral([ $literal1, $literal2 ])
        );

        $this->assertInstanceOf(
            SimpleTypeInterface::class,
            $deWorkbench->validateDeInstance($validDeInstance)
        );

        $invalidDeInstance = new DeInstance(
            $dataElement,
            new ConstructedStringLiteral([ $literal1, $literal3 ])
        );

        $this->expectException(InvalidType::class);

        $this->expectExceptionMessage(
            'Invalid type "http://www.w3.org/2001/XMLSchema dura...", '
                . 'expected one of "derived from http://www.w3.org/2001/X..."'
        );

        $deWorkbench->validateDeInstance($invalidDeInstance);
    }

    /**
     * @dataProvider createDeInstanceProvider
     */
    public function testCreateDeInstance($value, $expectedDeInstance): void {
        $deWorkbench = DeWorkbench::getMainInstance();

        $this->assertTrue(
            $expectedDeInstance->equals(
                $deWorkbench->createDeInstance(
                    $value,
                    $expectedDeInstance->getDataElement()
                )
            )
        );
    }

    public function createDeInstanceProvider(): array
    {
        $deWorkbench = DeWorkbench::getMainInstance();

        $schema = $deWorkbench->getSchema();

        return [
            [
                42,
                new DeInstance(
                    $deWorkbench->createDataElementFromXName(
                        self::XSD_NS . ' unsignedShort'
                    ),
                    new IntegerLiteral(
                        42,
                        $schema->getGlobalType(self::XSD_NS . ' unsignedShort')
                            ->getUri()
                    )
                )
            ],
            [
                [ 'a' => 'foo', 'b' => true ],
                new DeInstance(
                    new ConstructedDataElement(
                        [
                            'a' => $deWorkbench->createDataElementFromXName(
                                self::XSD_NS . ' NCName'
                            ),
                            'b' => $deWorkbench->createDataElementFromXName(
                                self::XSD_NS . ' boolean'
                            ),
                            'c' => $deWorkbench->createDataElementFromXName(
                                self::XSD_NS . ' unsignedLong'
                            ),
                            'd' => $deWorkbench->createDataElementFromXName(
                                self::XSD_NS . ' duration'
                            )
                        ]
                    ),
                    new ConstructedStringLiteral(
                        [
                            'a' => new StringLiteral(
                                'foo',
                                $schema->getGlobalType(self::XSD_NS . ' NCName')
                                    ->getUri()
                            ),
                            'b' => new BooleanLiteral(true),
                            'c' => new IntegerLiteral(
                                0,
                                $schema
                                    ->getGlobalType(
                                        self::XSD_NS . ' unsignedLong'
                                    )
                                    ->getUri()
                            ),
                            'd' => new DurationLiteral('P0D')
                        ]
                    )
                )
            ],
            [
                [ 'ab', 'https://example.com', 'bar:baz' ],
                new DeInstance(
                    new ConstructedDataElement(
                        [
                            $deWorkbench->createDataElementFromXName(
                                self::XSD_NS . ' hexBinary'
                            ),
                            $deWorkbench->createDataElementFromXName(
                                self::XSD_NS . ' anyURI'
                            ),
                            $deWorkbench->createDataElementFromXName(
                                self::XSD_NS . ' QName'
                            )
                        ],
                        $schema->getGlobalType(self::XSD_NS . ' hexBinary')
                    ),
                    new ConstructedHexBinaryLiteral(
                        [
                            new HexBinaryLiteral('AB'),
                            new AnyUriLiteral('https://example.com'),
                            new QNameLiteral('bar:baz')
                        ]
                    )
                )
            ]
        ];
    }
}
