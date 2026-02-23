<?php

namespace alcamo\data_element;

use alcamo\range\NonNegativeRange;
use alcamo\rdfa\{FourBitStringLiteral, LiteralInterface};

class FourBitStringSerializer extends AbstractSerializerWithEncoding
{
    public const SUPPORTED_DATATYPE_XNAMES =
        [ FourBitStringLiteral::DATATYPE_XNAME ];

    public const DEFAULT_DATATYPE_URI = FourBitStringLiteral::DATATYPE_URI;

    public const SUPPORTED_LITERAL_CLASSES = [ FourBitStringLiteral::class ];

    public const SUPPORTED_ENCODINGS = [ 'ASCII', 'FOUR-BIT' ];

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

            case 'FOUR-BIT':
                return
                    hex2bin($this->adjustOutputLength($literal->toHex(), 'F'));
    }

    public function deserialize(string $input): LiteralInterface
    {
        if ($this->encoding_ == 'FOUR-BIT') {
            $input = bin2hex($input);
        }

        $this->validateInputLength($input);

        return $this->encoding_ == 'FOUR-BIT'
            ? FourBitStringLiteral::newFromHex(
                $input,
                $this->dataElement_->getDatatype()->getUri()
            )
            : new FourBitStringLiteral(
                $input,
                $this->dataElement_->getDatatype()->getUri()
            );
    }
}
