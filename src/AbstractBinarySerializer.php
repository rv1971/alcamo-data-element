<?php

namespace alcamo\data_element;

use alcamo\binary_data\BinaryString;
use alcamo\rdfa\LiteralInterface;

/**
 * @brief (De)Serializer for binary data
 *
 * @date Last reviewed 2026-02-24
 */
abstract class AbstractBinarySerializer extends AbstractSerializer
{
    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        $value = $literal->getValue()->getData();

        return $this->adjustOutputLength(
            $value instanceof BinaryString ? $value->getData() : (string)$value,
            "\x00"
        );
    }

    public function deserialize(string $input): LiteralInterface
    {
        $this->validateInputLength($input);

        $class = static::SUPPORTED_LITERAL_CLASSES[0];

        return new $class(
            new BinaryString($input),
            $this->dataElement_->getDatatype()->getUri()
        );
    }
}
