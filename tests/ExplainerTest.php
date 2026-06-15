<?php

namespace alcamo\data_element;

use alcamo\rdf_literal\{
    ConstructedStringLiteral,
    HexBinaryLiteral,
    StringLiteral
};
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class ExplainerTest extends TestCase
{
    public const FOO_NS = 'http://foo.example.org/';

    /**
     * @dataProvider explainAsMarkdownTextProvider
     */
    public function testExplainAsMarkdownText(
        $deInstance,
        $lang,
        $expectedText
    ): void {
        $explainer = new Explainer($lang);

        $this->assertSame(
            $expectedText,
            (string)$explainer->explainAsMarkdownText($deInstance)
        );
    }

    public function explainAsMarkdownTextProvider(): array
    {
        $schema = DeWorkbench::getMainInstance()->getSchema();

        $schema->addUris(
            [
                (new FileUriFactory())
                    ->create(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xsd')
            ]
        );

        $myTokenType = $schema->getGlobalType(self::FOO_NS . ' MyToken');
        $myTokenUri = $myTokenType->getUri();

        $myTokenDataElement = new DataElement($myTokenType);

        $fooBarType = $schema->getGlobalType(self::FOO_NS . ' FooBar');
        $fooBarUri = $fooBarType->getUri();

        $fooBarDataElement = new DataElement($fooBarType);

        $fooBarBazQuxType =
            $schema->getGlobalType(self::FOO_NS . ' FooBarBazQux');
        $fooBarBazQuxUri = $fooBarBazQuxType->getUri();

        $fooBarBazQuxDataElement = new DataElement(
            $fooBarBazQuxType,
            [ [ 'rdfs:label', 'FBBQ' ] ]
        );

        $quuxType = $schema->getGlobalType(self::FOO_NS . ' Quux');
        $quuxUri = $quuxType->getUri();

        $quuxDataElement = new DataElement($quuxType);

        $constructedDataElement = new ConstructedDataElement(
            [
                $myTokenDataElement,
                $fooBarDataElement,
                $fooBarBazQuxDataElement,
                $quuxDataElement
            ]
        );

        return [
            [
                new DeInstance(
                    $myTokenDataElement,
                    new StringLiteral('foofoo', $myTokenUri)
                ),
                null,
                "My token"
            ],
            [
                new DeInstance(
                    $myTokenDataElement,
                    new StringLiteral('barbar', $myTokenUri)
                ),
                'de-BE',
                "Mein Token"
            ],
            [
                new DeInstance(
                    $fooBarDataElement,
                    new StringLiteral('FOO', $fooBarUri)
                ),
                'de',
                "Foo/bar: Foo"
            ],
            [
                new DeInstance(
                    $fooBarDataElement,
                    new StringLiteral('FOO', $fooBarUri)
                ),
                'it',
                'Foo/bar: il valore "Foo"'
            ],
            [
                new DeInstance(
                    $fooBarDataElement,
                    new StringLiteral('BAR', $fooBarUri)
                ),
                null,
                'Foo/bar'
            ],
            [
                new DeInstance(
                    $fooBarBazQuxDataElement,
                    new StringLiteral('FOO', $fooBarBazQuxUri)
                ),
                'it-IS',
                'FBBQ: il valore "Foo"'
            ],
            [
                new DeInstance(
                    $fooBarBazQuxDataElement,
                    new StringLiteral('BAZ', $fooBarBazQuxUri)
                ),
                null,
                'FBBQ: Baz'
            ],
            [
                new DeInstance(
                    $fooBarBazQuxDataElement,
                    new StringLiteral('QUX', $fooBarBazQuxUri)
                ),
                null,
                'FBBQ'
            ],
            [
                new DeInstance(
                    $quuxDataElement,
                    new HexBinaryLiteral('2000', $quuxUri)
                ),
                null,
                'Q.u.u.x.: baz'
            ],
            [
                new DeInstance(
                    $quuxDataElement,
                    new HexBinaryLiteral('6000', $quuxUri)
                ),
                null,
                "Q.u.u.x.\n"
                . "* bar\n"
                . "* baz"
            ],
            [
                new DeInstance(
                    $quuxDataElement,
                    new HexBinaryLiteral('E400', $quuxUri)
                ),
                null,
                "Q.u.u.x.\n"
                . "* foo-baz\n"
                . "* bar\n"
                . "* baz\n"
                . "* qux"
            ],
            [
                new ConstructedDeInstance(
                    $constructedDataElement,
                    new ConstructedStringLiteral(
                        [
                            new StringLiteral('barbar', $myTokenUri),
                            new StringLiteral('FOO', $fooBarUri),
                            new StringLiteral('BAZ', $fooBarBazQuxUri),
                            new HexBinaryLiteral('6400', $quuxUri)
                        ]
                    )
                ),
                null,
                "string\n"
                . " 1. My token\n"
                . " 2. Foo/bar: Foo\n"
                . " 3. FBBQ: Baz\n"
                . " 4. Q.u.u.x.\n"
                . "    * bar\n"
                . "    * baz\n"
                . "    * qux"
            ]
        ];
    }
}
