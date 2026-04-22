<?php

namespace alcamo\data_element;

use alcamo\dom\schema\SchemaFactory;
use alcamo\rdf_literal\{
    Base64BinaryLiteral,
    BooleanLiteral,
    HexBinaryLiteral,
    LiteralFactory as RdfLiteralFactory,
    NonNegativeIntegerLiteral,
    StringLiteral
};
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class LiteralFactoryTest extends TestCase
{
    public const XSD_NS = SchemaFactory::XSD_NS;

    public const FOO_NS = 'http://foo.example.org/';

    private static $literalFactory_;

    public static function setUpBeforeClass(): void
    {
        self::$literalFactory_ = new LiteralFactory();

        self::$literalFactory_->getSchemaFactory()->getMainSchema()
            ->addUris(
                [
                    (new FileUriFactory())
                        ->create(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xsd')
                ]
            );
    }

    public function testBasics(): void
    {
        $this->assertInstanceOf(
            RdfLiteralFactory::class,
            self::$literalFactory_->getRdfLiteralFactory()
        );

        $schema = self::$literalFactory_->getSchemaFactory()->getMainSchema();

        $this->assertSame(
            Base64BinaryLiteral::class,
            self::$literalFactory_->getTypeToLiteralClass()->lookup(
                $schema->getGlobalType(self::XSD_NS . ' base64Binary')
            )
        );

        $this->assertSame(
            NonNegativeIntegerLiteral::class,
            self::$literalFactory_->getTypeToLiteralClass()->lookup(
                $schema->getGlobalType(self::XSD_NS . ' unsignedInt')
            )
        );
    }

    /**
     * @dataProvider createLiteralForDataElementProvider
     */
    public function testCreateLiteralForDataElement(
        $datatypeXName,
        $value,
        $expectedLiteral
    ): void {
        $schema = self::$literalFactory_->getSchemaFactory()->getMainSchema();

        $datatype = $schema->getGlobalType($datatypeXName);

        $literal = self::$literalFactory_->create($value, $datatype);

        /* The literal objects as a whole are often not the same because their
         * datatype URIs differ: for instance, one refers to
         * http://www.w3.org/2001/XMLSchema while the other one refers to a
         * local copy. */

        $this->assertSame(get_class($expectedLiteral), get_class($literal));

        $this->assertEquals($expectedLiteral->getValue(), $literal->getValue());

        $this->assertEquals($datatype->getUri(), $literal->getDatatypeUri());
    }

    public function createLiteralForDataElementProvider(): array
    {
        return [
            [
                self::XSD_NS . ' boolean',
                true,
                new BooleanLiteral(true)
            ],
            [
                self::XSD_NS . ' hexBinary',
                '123ABC',
                new HexBinaryLiteral(
                    '123ABC',
                )
            ],
            [
                self::XSD_NS . ' unsignedByte',
                42,
                new NonNegativeIntegerLiteral(42)
            ],
            [
                self::FOO_NS . ' MyString',
                'Lorem ipsum',
                new StringLiteral('Lorem ipsum')
            ]
        ];
    }
}
