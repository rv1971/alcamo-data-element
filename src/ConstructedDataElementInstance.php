<?php

namespace alcamo\data_element;

use alcamo\collection\ReadonlyCollectionTrait;
use alcamo\exception\DataValidationFailed;
use alcamo\rdf_literal\ConstructedLiteral;

/**
 * @brief Constructed data element instance containing data element instances
 * for subfields
 *
 * The ArrayAccess and Iterator interfaces gice access to the single data
 * elements.
 *
 * @date Last reviewed 2026-05-05
 */
class ConstructedDataElementInstance extends DataElementInstance implements
    \Countable,
    \ArrayAccess,
    \Iterator
{
    use ReadonlyCollectionTrait;

    public function __construct(
        ConstructedDataElement $dataElement,
        ConstructedLiteral $literal
    ) {
        if (count($literal) != count($dataElement)) {
            /** @todo throw alcamo::exception::DataValidationFailed if the
             *  literal count does not match the data element count. */
            throw (new DataValidationFailed())->setMessageContext(
                [
                    'extraMessage' => 'literal count ' . count($literal)
                        . ' does not match data element count '
                        . count($dataElement)
                ]
            );
        }

        parent::__construct($dataElement, $literal);

        $literal->rewind();

        foreach ($dataElement as $key => $dataElementItem) {
            $this->data_[$key] =
                new DataElementInstance($dataElementItem, $literal->current());

            $literal->next();
        }
    }
}
