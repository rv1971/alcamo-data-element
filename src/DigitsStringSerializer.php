<?php

namespace alcamo\data_element;

use alcamo\range\NonNegativeRange;
use alcamo\rdfa\{DigitsStringLiteral, LiteralInterface};

class DigitsStringSerializer extends AbstractSerializerWithEncoding
{
    public const SUPPORTED_DATATYPE_XNAMES =
        [ DigitsStringLiteral::DATATYPE_XNAME ];

    public const DEFAULT_DATATYPE_URI = DigitsStringLiteral::DATATYPE_URI;

    public const SUPPORTED_LITERAL_CLASSES = [ DigitsStringLiteral::class ];

    public const SUPPORTED_ENCODINGS = [ 'ASCII', 'COMPRESSED-BCD' ];

    public const DEFAULT_ENCODING = 'ASCII';

    public function serialize(?LiteralInterface $literal = null): string
    {
        if (isset($literal)) {
            $this->validateLiteralClass($literal);
        } else {
            $literal = $this->dataElement_->createLiteral();
        }

        switch ($this->encoding_) {
            case 'ASCII':
                return $this->adjustOutputLength($literal);

            case 'COMPRESSED-BCD':
                return hex2bin($this->adjustOutputLength($literal, 'F'));
    }

    public function deserialize(string $input): LiteralInterface
    {
        if ($this->encoding_ == 'COMPRESSED-BCD') {
            $input = bin2hex($input);
        }

        $this->validateInputLength($input);

        return new DigitsStringLiteral(
            rtrim($input, ' F'),
            $this->dataElement_->getDatatype()->getUri()
        );
    }
}
