<?php

namespace alcamo\data_element;

use alcamo\collection\{CollectionInterface, ReadonlyCollection};
use alcamo\exception\DataValidationFailed;
use alcamo\rdf_literal\{AbstractConstructedLiteral, LiteralInterface};

/**
 * @brief Data element instance
 *
 * @invariant Immutable class.
 *
 * @date Last reviewed 2026-05-05
 */
class DeInstance implements DeInstanceInterface
{
    private $dataElement_;      ///< DataElementInterface
    private $literal_;          ///< LiteralInterface
    private $children_ = false; ///< ?CollectionInterface

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

    public function hasChildren(): bool
    {
        return $this->dataElement_ instanceof ConstructedDataElement
            && $this->literal_ instanceof AbstractConstructedLiteral;
    }

    public function getChildren(): ?CollectionInterface
    {
        if ($this->children_ === false) {
            if ($this->hasChildren()) {
                if (count($this->literal_) != count($this->dataElement_)) {
                    /** @todo throw alcamo::exception::DataValidationFailed if
                     *  the literal count does not match the data element
                     *  count. */
                    throw (new DataValidationFailed())->setMessageContext(
                        [
                            'extraMessage' => 'literal count '
                                . count($this->literal_)
                                . ' does not match data element count '
                                . count($this->dataElement_)
                        ]
                    );
                }

                $children = [];

                $this->literal_->rewind();

                foreach ($this->dataElement_ as $key => $dataElementItem) {
                    $children[$key] = new DeInstance(
                        $dataElementItem,
                        $this->literal_->current()
                    );

                    $this->literal_->next();
                }

                $this->children_ = new ReadonlyCollection($children);
            } else {
                $this->children_ = null;
            }
        }

        return $this->children_;
    }

    public function equals(object $deInstance): bool
    {
        return $deInstance instanceof DeInstanceInterface
            && $this->dataElement_ == $deInstance->dataElement_
            && $this->literal_->equals($deInstance->literal_);
    }
}
