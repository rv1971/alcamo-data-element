<?php

namespace alcamo\data_element;

use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\exception\{ErrorHandler, Unsupported};
use alcamo\input_stream\StringInputStream;
use alcamo\rdf_literal\LiteralInterface;

/**
 * @brief (De)Serializer for string data
 *
 * @date Last reviewed 2026-04-21
 */
class StringSerializer extends AbstractSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        self::XSD_NS . ' string',
        self::XSD_NS . ' anyURI',
        self::XSD_NS . ' NOTATION',
        self::XSD_NS . ' QName'
    ];

    public const ENCODINGS = [
        'UTF-8' => [ 8, ' ', STR_PAD_RIGHT ], // default encoding
        '*'     => [ 8, ' ', STR_PAD_RIGHT ]
    ];

    /// String encoding used internally
    public const INTERNAL_ENCODING = 'UTF-8';

    public function serialize(
        LiteralInterface $literal,
        ?int $length = null
    ): string {
        $this->validateLiteralClass($literal);

        $value = $literal->getValue();

        $encoding = $this->encodingParams_->getEncoding();

        if ($encoding == static::INTERNAL_ENCODING) {
            return $this->adjustOutputLength($value, $length);
        }

        /* Pad to minimum length in internal encoding before character set
         * conversion takes place, because output encoding might have a
         * different representation of the padding character. */
        if (isset($length) || isset($this->lengthRange_)) {
            $value = $this->encodingParams_
                ->pad($value, $length ?? $this->lengthRange_->getMin());
        }

        $errorHandler = new ErrorHandler();

        try {
            $convertedValue =
                iconv(static::INTERNAL_ENCODING, $encoding, $value);
        } catch (\ErrorException $e) {
            $convertedValue = false;
        }

        /* If the iconv() installation does not support EBCDIC, convert via
         * ASCII. */
        if ($convertedValue === false && $encoding == 'EBCDIC') {
            try {
                $convertedValue =
                    iconv(static::INTERNAL_ENCODING, 'ASCII//TRANSLIT', $value);
            } catch (\ErrorException $e) {
                $convertedValue = false;
            }

            if ($convertedValue !== false) {
                $convertedValue = strtr(
                    $convertedValue,
                    EncodingParams::ASCII_CHARS,
                    EncodingParams::EBCDIC_CHARS
                );
            }
        }

        if ($convertedValue === false) {
            /** @throw alcamo::exception::Unsupported if conversion fails. */
            throw (new Unsupported())->setMessageContext(
                [ 'feature' => "conversion to $encoding" ]
            );
        }

        return $this->adjustOutputLength($convertedValue, $length);
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        $input = $this->preprocessInput($input);

        $encoding = $this->encodingParams_->getEncoding();

        /** Remove trailing spaces from input after conversion to internal
         *  encoding. */

        if ($encoding == static::INTERNAL_ENCODING) {
            return $this->deWorkbench_->createLiteral(
                rtrim($input),
                $datatype ?? $this->datatype_
            );
        }

        $errorHandler = new ErrorHandler();

        try {
            $convertedValue =
                iconv($encoding, static::INTERNAL_ENCODING, $input);
        } catch (\ErrorException $e) {
            $convertedValue = false;
        }

        /* If the iconv() installation does not support EBCDIC, convert via
         * ASCII. */
        if ($convertedValue === false && $encoding == 'EBCDIC') {
            try {
                $convertedValue = iconv(
                    'ASCII',
                    static::INTERNAL_ENCODING,
                    strtr(
                        $input,
                        EncodingParams::EBCDIC_CHARS,
                        EncodingParams::ASCII_CHARS,
                    )
                );
            } catch (\ErrorException $e) {
                $convertedValue = false;
            }
        }

        if ($convertedValue === false) {
            /** @throw alcamo::exception::Unsupported if conversion fails. */
            throw (new Unsupported())->setMessageContext(
                [ 'feature' => "conversion from $encoding" ]
            );
        }

        return $this->deWorkbench_->createLiteral(
            rtrim($convertedValue),
            $datatype ?? $this->datatype_
        );
    }

    public function dump(LiteralInterface $literal): string
    {
        return (new Dumper())
            ->dumpString($literal, $this->encodingParams_->getEncoding());
    }

    public function dedump(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        return
            $this->dedumpFromStream(new StringInputStream($input), $datatype);
    }

    public function dedumpFromStream(
        StringInputStream $istream,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        return $this->deWorkbench_->createLiteral(
            (new Dumper())->dedumpStringFromStream(
                $istream,
                $this->encodingParams_->getEncoding()
            ),
            $datatype ?? $this->datatype_
        );
    }
}
