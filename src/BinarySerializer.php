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
        $input = $this->preprocessInput($input);

        return $this->deWorkbench_->createLiteral(
            new ImmutableBinaryString($input),
            $datatype ?? $this->datatype_
        );
    }

    public function dump(LiteralInterface $literal): string
    {
        return (new Dumper())->dumpBinary($literal->getValue());
    }

    public function dedump(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        return $this->deWorkbench_->createLiteral(
            (new Dumper())->dedumpBinary($input),
            $datatype ?? $this->datatype_
        );
    }
}
