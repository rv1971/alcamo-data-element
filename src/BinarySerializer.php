<?php

namespace alcamo\data_element;

use alcamo\binary_data\BinaryString;
use alcamo\dom\schema\component\SimpleTypeInterface;
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

    public const ENCODINGS = [
        'BINARY' => [ 8, "\x00" ],
        'DUMP'   => [ 8, '' ]
    ];

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        if ($this->encoding_ == 'DUMP') {
            return "'$literal'";
        }

        /* getValue() must return BinaryString. */
        return $this->adjustOutputLength($literal->getValue()->getData());
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        if ($this->encoding_ == 'DUMP') {
            if (!preg_match("/^'[0-9A-Fa-f]*'$/", $input)) {
                /** @throw alcamo::exception::SyntaxError on attempt to
                 *  deserialize with DUMP encoding an input which is not a
                 *  hex string enclosed in single quotes. */
                throw (new SyntaxError())->setMessageContext(
                    [ 'inData' => $input ]
                );
            }

            return $this->literalWorkbench_->createLiteral(
                BinaryString::newFromHex(trim($input, "'")),
                $datatype ?? $this->datatype_
            );
        }

        $this->validateInputLength($input);

        return $this->literalWorkbench_->createLiteral(
            new BinaryString($input),
            $datatype ?? $this->datatype_
        );
    }
}
