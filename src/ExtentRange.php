<?php

namespace alcamo\data_element;

use alcamo\range\NonNegativeRange;

class ExtentRange extends NonNegativeRange
{
    public const BYTES  = 'AD';
    public const DIGITS = '06';

    public const X12_UNIT_TO_LABEL = [
        self::BYTES => 'bytes',
        self::DIGITS => 'digits'
    ];

    public static function newFromNonNegativeRange(
        ?NonNegativeRangen $range = null,
        ?string $x12Unit = null
    ): self {
        return isset($range)
            ? new static($range->getMin(), $range->getMax(), $x12Unit)
            : new static(null, null, $x12Unit);
    }

    private $x12Unit_; ///< string

    public function __construct(
        ?int $min = null,
        ?int $max = null,
        ?string $x12Unit = null
    ) {
        parent::__construct($min, $max);

        $this->x12Unit_ = $x12Unit ?? self::BYTES;
    }

    public const __toString(): string
    {
        return parent::__toString() .
    }

    public function getMinMax(): array
    {
        return $this->range_->getMinMax();
    }

    public function getX12Unit(): string
    {
        return $this->x12Unit_;
    }
}
