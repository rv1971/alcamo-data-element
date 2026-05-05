<?php

namespace alcamo\data_element;

use PHPUnit\Framework\TestCase;

class MarkdownTextTest extends TestCase
{
    /**
     * @dataProvider newFromTextProvider
     */
    public function testNewFromText($text, $fold, $expectedResult): void
    {
        $this->assertSame(
            $expectedResult,
            (string)MarkdownText::newFromText($text, $fold)
        );
    }

    public function newFromTextProvider(): array
    {
        return [
            [ 'Lorem ipsum', null, 'Lorem ipsum' ],
            [
                "Lorem ipsum\ndolor sit amet",
                null,
                "Lorem ipsum\ndolor sit amet"
            ],
            [
                'Lorem ipsum dolor sit amet,',
                20,
                "Lorem ipsum dolor\nsit amet,"
            ],
            [
                'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, '
                    . 'sed diam nonumy eirmod',
                true,
                'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, '
                    . "sed diam nonumy\neirmod",

            ]
        ];
    }

    public function testAppendLine(): void
    {
        $markdownText = MarkdownText::newFromText('Lorem ipsum');

        $this->assertSame(
            "Lorem ipsum\ndolor sit amet,",
            (string)$markdownText->appendLine('dolor sit amet,')
        );
    }

    public function testAppendLines(): void
    {
        $markdownText = MarkdownText::newFromText('Lorem ipsum');

        $this->assertSame(
            "Lorem ipsum\ndolor sit amet,\nconsetetur",
            (string)$markdownText
                ->appendLines([ 'dolor sit amet,', 'consetetur' ])
        );
    }

    public function testAppendMarkdownText(): void
    {
        $markdownText = MarkdownText::newFromText('Lorem ipsum');

        $this->assertSame(
            "Lorem ipsum\ndolor sit amet,",
            (string)$markdownText->appendMarkdownText(
                MarkdownText::newFromText('dolor sit amet,')
            )
        );
    }

    public function testPrependLine(): void
    {
        $markdownText = MarkdownText::newFromText('dolor sit amet,');

        $this->assertSame(
            "Lorem ipsum\ndolor sit amet,",
            (string)$markdownText->prependLine('Lorem ipsum')
        );
    }

    public function testPrependLines(): void
    {
        $markdownText = MarkdownText::newFromText('consetetur');

        $this->assertSame(
            "Lorem ipsum\ndolor sit amet,\nconsetetur",
            (string)$markdownText
                ->prependLines([ 'Lorem ipsum', 'dolor sit amet,' ])
        );
    }

    public function testPrependMarkdownText(): void
    {
        $markdownText = MarkdownText::newFromText('dolor sit amet,');

        $this->assertSame(
            "Lorem ipsum\ndolor sit amet,",
            (string)$markdownText->prependMarkdownText(
                MarkdownText::newFromText('Lorem ipsum')
            )
        );
    }

    public function testToQuote(): void
    {
        $this->assertSame(
            "> Lorem\n> ipsum",
            (string)MarkdownText::newFromText("Lorem\nipsum")->toQuote()
        );
    }

    public function testToBulletListItem(): void
    {
        $this->assertSame(
            "* Lorem\n  ipsum",
            (string)MarkdownText::newFromText("Lorem\nipsum")
                ->toBulletListItem()
        );

        $this->assertSame(
            "- dolor",
            (string)MarkdownText::newFromText("dolor")->toBulletListItem('-')
        );
    }

    public function testToOrderedListItem(): void
    {
        $this->assertSame(
            "42. Lorem\n    ipsum",
            (string)MarkdownText::newFromText("Lorem\nipsum")
                ->toOrderedListItem(42)
        );

        $this->assertSame(
            " 43) dolor",
            (string)MarkdownText::newFromText("dolor")
                ->toOrderedListItem(43, 3, ')')
        );
    }
}
