<?php

namespace alcamo\data_element;

use alcamo\binary_data\BinaryString;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\LiteralInterface;

/**
 * @brief (De)Serializer for binary data
 *
 * @date Last reviewed 2026-04-21
 */
class BinarySerializer extends AbstractSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        self::XSD_NS . ' hexBinary',
        self::XSD_NS . ' base64Binary'
    ];

    public const DEFAULT_ENCODING = 'BINARY';

    public const ENCODING_TO_PAD_STRING = [ 'BINARY' => "\x00" ];

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        /* getValue() must return BinaryString. */
        return $this->adjustOutputLength($literal->getValue()->getData());
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        $this->validateInputLength($input);

        return $this->literalWorkbench_->createLiteral(
            new BinaryString($input),
            $datatype ?? $this->datatype_
        );
    }
}
