<?php

namespace alcamo\data_element;

/**
 * @brief Simple class modeling an array of lines of markdown text
 *
 * @date Last reviewed 2026-05-05
 */
class MarkdownText
{
    private $lines_; ///< array of text lines

    public static function newFromText(string $text, $fold = null): self
    {
        return new self(
            $fold
                ? explode(
                    "\n",
                    wordwrap($text, $fold === true ? 75 : (int)$fold)
                )
                : explode("\n", $text)
        );
    }

    public function __construct(?array $lines = null)
    {
        $this->lines_ = $lines;
    }

    /// Text lines separated by linefeed, no trailing linefeed at the end
    public function __toString(): string
    {
        return implode("\n", $this->lines_);
    }

    public function appendLine(string $line): self
    {
        $this->lines_[] = $line;

        return $this;
    }

    public function appendLines(array $lines): self
    {
        $this->lines_ = array_merge($this->lines_, $lines);

        return $this;
    }

    public function appendMarkdownText(self $markdownText): self
    {
        $this->lines_ = array_merge($this->lines_, $markdownText->lines_);

        return $this;
    }

    public function prependLine(string $line): self
    {
        array_unshift($this->lines_, $line);

        return $this;
    }

    public function prependLines(array $lines): self
    {
        $this->lines_ = array_merge($lines, $this->lines_);

        return $this;
    }

    public function prependMarkdownText(self $markdownText): self
    {
        $this->lines_ = array_merge($markdownText->lines_, $this->lines_);

        return $this;
    }

    public function indent(string $prefix, ?string $firstPrefix = null): self
    {
        if (!isset($firstPrefix)) {
            $firstPrefix = $prefix;
        }

        $isFirstLine = true;

        foreach ($this->lines_ as $key => $line) {
            $this->lines_[$key] = ($isFirstLine ? $firstPrefix : $prefix)
                . $line;

            $isFirstLine = false;
        }

        return $this;
    }

    public function toQuote(): self
    {
        return $this->indent('> ');
    }

    public function toBulletListItem(?string $bulletListMarker = null): self
    {
        return $this->indent('  ', ($bulletListMarker ?? '*') . ' ');
    }

    public function toOrderedListItem(
        int $no,
        ?int $width = null,
        ?string $orderedListChar = null
    ): self {
        if (!isset($width)) {
            $width = 2;
        }

        if (!isset($orderedListChar)) {
            $orderedListChar = '.';
        }

        return $this->indent(
            str_pad('', $width + 2),
            sprintf("%{$width}d$orderedListChar ", $no)
        );
    }
}
