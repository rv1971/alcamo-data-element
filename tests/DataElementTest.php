<?php

namespace alcamo\data_element;

use alcamo\dom\schema\SchemaFactory;
use alcamo\rdfa\RdfaData;
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class DataElementTest extends TestCase
{
    public const XSD_NS = SchemaFactory::XSD_NS;

    public const FOO_NS = 'http://foo.example.org/';

    private static $schema_;

    public static function setUpBeforeClass(): void
    {
        self::$schema_ = (new SchemaFactory())->createFromUris(
            [
                (new FileUriFactory())
                    ->create(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xsd')
            ]
        );
    }

    /**
     * @dataProvider basicsProvider
     */
    public function testBasics(
        $typeXName,
        $rdfaData,
        $expectedRdfaData
    ) {
        $datatype = self::$schema_->getGlobalType($typeXName);

        $dataElement = new DataElement($datatype, $rdfaData);

        $this->assertSame($datatype, $dataElement->getDatatype());

        $this->assertEquals(
            RdfaData::newFromIterable((array)$expectedRdfaData),
            $dataElement->getRdfaData()
        );
    }

    public function basicsProvider(): array
    {
        return [
            [
                self::XSD_NS . ' unsignedShort',
                null,
                [ [ 'rdfs:label', 'unsignedShort' ] ]
            ],
            [
                self::FOO_NS . ' MyString',
                [ [ 'dc:identifier', 'my-string' ] ],
                [
                    [ 'rdfs:label', 'My string' ],
                    [ 'dc:identifier', 'my-string' ]
                ]
            ],
            [
                self::FOO_NS . ' MyString',
                [
                    [ 'dc:identifier', 'my-string' ],
                    [ 'rdfs:label', 'Another label' ]
                ],
                [
                    [ 'rdfs:label', 'Another label' ],
                    [ 'dc:identifier', 'my-string' ]
                ]
            ]
        ];
    }
}
