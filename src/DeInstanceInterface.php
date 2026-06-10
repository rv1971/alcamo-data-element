<?php

namespace alcamo\data_element;

use alcamo\rdf_literal\LiteralInterface;

/**
 * @brief Instance of a data element with a literal as its value
 *
 * @date Last reviewed 2026-05-04
 */
interface DeInstanceInterface
{
    public function getDataElement(): DataElementInterface;

    public function getLiteral(): LiteralInterface;

    /**
     * @brief Whether $this and $deInstance are considered equal
     *
     * This is the case iff both belong to the same data element and the
     * literals are considered equal.
     */
    public function equals(self $deInstance): bool;
}
