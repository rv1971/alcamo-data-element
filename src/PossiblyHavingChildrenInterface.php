<?php

namespace alcamo\data_element;

use alcamo\collection\CollectionInterface;

/**
 * @brief Ibject that may have children
 *
 * @date Last reviewed 2026-08-08
 */
interface PossiblyHavingChildrenInterface
{
    /// Whether this object has children
    public function hasChildren(): bool;

    /// Children of this object, if any
    public function getChildren(): ?CollectionInterface;
}
