<?php

namespace alcamo\data_element;

use alcamo\rdf_literal\{ConstructedLiteral, StringLiteral};
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class ExplainerTest extends TestCase
{
    public const FOO_NS = 'http://foo.example.org/';

    /**
     * @dataProvider explainAsMarkdownTextProvider
     */
    public function testExplainAsMarkdownText(
        $dataElementInstance,
        $lang,
        $expectedText
    ): void {
        $explainer = new Explainer($lang);

        $this->assertSame(
            $expectedText,
            (string)$explainer->explainAsMarkdownText($dataElementInstance)
        );
    }

    public function explainAsMarkdownTextProvider(): array
    {
        $schema = LiteralWorkbench::getMainInstance()->getSchema();

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

        $constructedDataElement = new ConstructedDataElement(
            [
                $myTokenDataElement,
                $fooBarDataElement,
                $fooBarBazQuxDataElement
            ]
        );

        return [
            [
                new DataElementInstance(
                    $myTokenDataElement,
                    new StringLiteral('foofoo', $myTokenUri)
                ),
                null,
                "My token"
            ],
            [
                new DataElementInstance(
                    $myTokenDataElement,
                    new StringLiteral('barbar', $myTokenUri)
                ),
                'de-BE',
                "Mein Token"
            ],
            [
                new DataElementInstance(
                    $fooBarDataElement,
                    new StringLiteral('FOO', $fooBarUri)
                ),
                'de',
                "Foo/bar: Foo"
            ],
            [
                new DataElementInstance(
                    $fooBarDataElement,
                    new StringLiteral('FOO', $fooBarUri)
                ),
                'it',
                'Foo/bar: il valore "Foo"'
            ],
            [
                new DataElementInstance(
                    $fooBarDataElement,
                    new StringLiteral('BAR', $fooBarUri)
                ),
                null,
                'Foo/bar'
            ],
            [
                new DataElementInstance(
                    $fooBarBazQuxDataElement,
                    new StringLiteral('FOO', $fooBarBazQuxUri)
                ),
                'it-IS',
                'FBBQ: il valore "Foo"'
            ],
            [
                new DataElementInstance(
                    $fooBarBazQuxDataElement,
                    new StringLiteral('BAZ', $fooBarBazQuxUri)
                ),
                null,
                'FBBQ: Baz'
            ],
            [
                new DataElementInstance(
                    $fooBarBazQuxDataElement,
                    new StringLiteral('QUX', $fooBarBazQuxUri)
                ),
                null,
                'FBBQ'
            ],
            [
                new ConstructedDataElementInstance(
                    $constructedDataElement,
                    new ConstructedLiteral(
                        [
                            new StringLiteral('barbar', $myTokenUri),
                            new StringLiteral('FOO', $fooBarUri),
                            new StringLiteral('BAZ', $fooBarBazQuxUri)
                        ]
                    )
                ),
                null,
                "string\n"
                . " 1. My token\n"
                . " 2. Foo/bar: Foo\n"
                . " 3. FBBQ: Baz"
            ]
        ];
    }
}
