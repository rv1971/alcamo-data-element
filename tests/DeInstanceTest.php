<?php

namespace alcamo\data_element;

use alcamo\dom\schema\SchemaFactory;
use alcamo\rdf_literal\StringLiteral;
use alcamo\rdfa\RdfaData;
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

        $this->assertSame($dataElement, $deInstance1->getDataElement());

        $this->assertSame($literal, $deInstance1->getLiteral());
    }

    /**
     * @dataProvider equalsProvider
     */
    public function testEquals(
        $deInstance1,
        $deInstance2,
        $expectedResult
    ): void {
        $this->assertTrue($deInstance1->equals($deInstance1));
        $this->assertTrue($deInstance2->equals($deInstance2));

        $this->assertSame($expectedResult, $deInstance1->equals($deInstance2));
        $this->assertSame($expectedResult, $deInstance2->equals($deInstance1));
    }

    public function equalsProvider(): array
    {
        $schema = (new SchemaFactory())->getMainSchema();

        $dataElements = [];

        $dataElements[1] = new DataElement(
            $schema->getGlobalType(self::XSD_NS . ' string'),
            RdfaData::newFromIterable([ [ 'rdfs:label', 'foo' ] ])
        );

        $dataElements[2] = new DataElement(
            $schema->getGlobalType(self::XSD_NS . ' string'),
            RdfaData::newFromIterable([ [ 'rdfs:label', 'foo' ] ])
        );

        $dataElements[3] = new DataElement(
            $schema->getGlobalType(self::XSD_NS . ' string')
        );

        $dataElements[4] = new DataElement(
            $schema->getGlobalType(self::XSD_NS . ' token')
        );

        $literals = [];

        $literals[1] =
            new StringLiteral('foo', self::XSD_NS . '#token');

        $literals[2] =
            new StringLiteral('foo', self::XSD_NS . '#Name');

        $literals[3] =
            new StringLiteral('bar', self::XSD_NS . '#token');

        $tests = [
            [ 1, 1, 1, 2, true ],
            [ 1, 1, 1, 3, false ],
            [ 1, 1, 2, 1, true ],
            [ 1, 1, 2, 2, true ],
            [ 1, 1, 2, 3, false ],
            [ 1, 1, 3, 1, false ],
            [ 1, 1, 3, 2, false ],
            [ 1, 1, 3, 3, false ],
            [ 1, 1, 4, 1, false ],
            [ 1, 1, 4, 2, false ],
            [ 1, 1, 4, 3, false ]
        ];

        $data = [];

        foreach ($tests as $test) {
            [ $d1, $l1, $d2, $l2, $expectedResult ] = $test;

            $data[] = [
                new DeInstance($dataElements[$d1], $literals[$l1]),
                new DeInstance($dataElements[$d2], $literals[$l2]),
                $expectedResult
            ];
        }

        return $data;
    }
}
