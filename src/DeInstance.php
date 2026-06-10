<?php

namespace alcamo\data_element;

use alcamo\rdf_literal\LiteralInterface;

/**
 * @brief Data element instance
 *
 * @invariant Immutable class.
 *
 * @date Last reviewed 2026-05-05
 */
class DeInstance implements DeInstanceInterface
{
    private $dataElement_; ///< DataElementInterface
    private $literal_;     ///< LiteralInterface

    public function __construct(
        DataElementInterface $dataElement,
        LiteralInterface $literal
    ) {
        $this->dataElement_ = $dataElement;
        $this->literal_ = $literal;
    }

    public function getDataElement(): DataElementInterface
    {
        return $this->dataElement_;
    }

    public function getLiteral(): LiteralInterface
    {
        return $this->literal_;
    }

    public function equals(DeInstanceInterface $deInstance): bool
    {
        return $this->dataElement_ == $deInstance->dataElement_
            && $this->literal_->equals($deInstance->literal_);
    }
}
