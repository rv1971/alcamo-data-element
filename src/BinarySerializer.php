<?php

namespace alcamo\data_element;

use alcamo\binary_data\ImmutableBinaryString;
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

    public const ENCODINGS = [ 'BINARY' => [ 8, "\x00", STR_PAD_RIGHT ] ];

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        /* getValue() returns ImmutableBinaryString. */
        return $this->adjustOutputLength($literal->getValue()->getData());
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        $this->validateInputLength($input);

        return $this->deWorkbench_->createLiteral(
            new ImmutableBinaryString($input),
            $datatype ?? $this->datatype_
        );
    }

    public function dump(LiteralInterface $literal): string
    {
        $data = $literal->getValue()->getData();

        switch (true) {
            /** If the data have more than three bytes and consist of
             *  a repetition of the same byte, represent it as a
             *  repetition, e.g. `01010101` as `4 * '01'`. */
            case strlen($data) > 3
                && $data == str_repeat($data[0], strlen($data)):
                return strlen($data)
                    . " * '" . (new ImmutableBinaryString($data[0])) . "'";

            /** If the data have have more than four bytes and consist of a
             *  repetition of the same two bytes, represent it as a
             *  repetition, e.g. `ABCDABCDABCD` as `3 * 'ABCD'`. */
            case strlen($data) > 4 && $data == str_repeat(
                $data[0] . $data[1],
                strlen($data) >> 1
            ):
                return (strlen($data) >> 1) . " * '"
                    . (new ImmutableBinaryString($data[0] . $data[1]))
                    . "'";

            default:
                return "'{$literal->getValue()}'";
        }
    }

    public function dedump(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        if (preg_match('/^(\d+)\s*\*\s*\'([^\']+)\'$/', $input, $matches)) {
            return $this->deWorkbench_->createLiteral(
                ImmutableBinaryString::newFromHex(
                    str_repeat($matches[2], $matches[1])
                ),
                $datatype ?? $this->datatype_
            );
        }

        if (!preg_match("/^'[0-9A-Fa-f]*'$/", $input)) {
            /** @throw alcamo::exception::SyntaxError on attempt to dedump an
             *  input which is neither a repetition as explained above nor a
             *  hex string enclosed in single quotes. */
            throw (new SyntaxError())->setMessageContext(
                [ 'inData' => $input ]
            );
        }

        return $this->deWorkbench_->createLiteral(
            ImmutableBinaryString::newFromHex(trim($input, "'")),
            $datatype ?? $this->datatype_
        );
    }
}
