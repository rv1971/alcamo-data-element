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

    public const ENCODINGS = [ 'BINARY' => [ 8, "\x00" ] ];

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

    public function dump(LiteralInterface $literal): string
    {
        return "'{$literal->getValue()}'";
    }

    public function dedump(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        if (!preg_match("/^'[0-9A-Fa-f]*'$/", $input)) {
            /** @throw alcamo::exception::SyntaxError on attempt to dedump an
             *  input which is not a hex string enclosed in single quotes. */
            throw (new SyntaxError())->setMessageContext(
                [ 'inData' => $input ]
            );
        }

        return $this->literalWorkbench_->createLiteral(
            BinaryString::newFromHex(trim($input, "'")),
            $datatype ?? $this->datatype_
        );
    }
}
