<?php

namespace alcamo\data_element;

use alcamo\collection\ReadonlyCollectionTrait;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\exception\InvalidType;
use alcamo\rdfa\RdfaData;

/**
 * @brief Constructed data element containing data elements for subfields
 *
 * @date Last reviewed 2026-05-04
 */
class ConstructedDataElement extends DataElement implements
    \Countable,
    \ArrayAccess,
    \Iterator
{
    use ReadonlyCollectionTrait;

    /**
     * @param $dataElements Iterable of DataElementInterface objects
     *
     * @param $datatype XSD datatype of the data element. [default xsd:string]
     *
     * @param RdfaData|array RDFa data about the data element
     */
    public function __construct(
        iterable $dataElements,
        ?SimpleTypeInterface $datatype = null,
        $rdfaData = null
    ) {
        foreach ($dataElements as $key => $dataElement) {
            if (!($dataElement instanceof DataElementInterface)) {
                /** @throw alcamo::exception::InvalidType if an item in
                 *  $dataElements is not a DataElementInterface object. */
                throw (new InvalidType())->setMessageContext(
                    [
                        'value' => $dataElement,
                        'expectedOneOf' => DataElementInterface::class
                    ]
                );
            }

            $this->data_[$key] = $dataElement;
        }

        parent::__construct(
            $datatype ?? reset($this->data_)->getDatatype()->getSchema()
                ->getGlobalType(self::XSD_NS . ' string'),
            $rdfaData
        );
    }
}
