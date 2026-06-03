<?php

namespace alcamo\data_element;

use alcamo\exception\{
    DataValidationFailed,
    InvalidEnumerator,
    LengthOutOfRange,
    SyntaxError
};

/**
 * @brief Parameters for a character encoding
 *
 * @date Last reviewed 2026-06-02
 */
class EncodingParams
{
    /// Supported values for bits per character
    public const SUPPORTED_BITS_PER_CHARACTER = [ 1, 4, 8 ];

    private $encoding_;         ///< string
    private $bitsPerCharacter_; ///< int, one of SUPPORTED_BITS_PER_CHARACTER
    private $padString_;        ///< ?string, nonempty if set
    private $padType_;          ///< ?int, one of STR_PAD_RIGHT or STR_PAD_LEFT

    /**
     * @parm $encoding Name of encoding.
     *
     * @param $bitsPerCharacter Number of bits represented by one
     * character. One of 1, 4, 8.
     *
     * @param $padString Nonempty padding string. Hex digit if
     * $bitsPerCharacter is 4, binary digit if $bitsPerCharacter is 1.
     *
     * @param $padType STR_PAD_RIGHT or STR_PAD_LEFT. Truncation, if
     * necessary, takes place on the same side as padding.
     */
    public function __construct(
        string $encoding,
        int $bitsPerCharacter,
        ?string $padString = null,
        ?int $padType = null
    ) {
        if (
            !in_array($bitsPerCharacter, static::SUPPORTED_BITS_PER_CHARACTER)
        ) {
            /** @throw alcamo::exception::InvalidEnumerator if
             *  $bitsPerCharacter is not 1, 4 or 8. */
            throw (new InvalidEnumerator())->setMessageContext(
                [
                    'value' => $bitsPerCharacter,
                    'expectedOneOf' => static::SUPPORTED_BITS_PER_CHARACTER
                ]
            );
        }

        if (isset($padType) != isset($padString)) {
            /** @throw alcamo::exception::DataValidationFailed unless
             *  $padString and $padType are both set or both `null`. */
            throw (new DataValidationFailed())->setMessageContext(
                [
                    'extraMessage' => isset($padType)
                        ? "padding type $padType contradicts "
                        . "unset padding string"
                        : ("padding string \"$padString\" contradicts "
                           . "unset padding type")
                ]
            );
        }

        if (isset($padString)) {
            if ($padString == '') {
                /** @throw alcamo::exception::DataValidationFailed if
                 *  $padString is empty. */
                throw (new DataValidationFailed())->setMessageContext(
                    [
                        'extraMessage' => 'empty padding string'
                    ]
                );
            }

            /** @throw alcamo::exception::DataValidationFailed if $padString
             *  is incompatible with the number of bits per character. */
            switch ($bitsPerCharacter) {
                case 1:
                    if (strspn($padString, '01') != strlen($padString)) {
                        throw (new DataValidationFailed())->setMessageContext(
                            [
                                'extraMessage' => "invalid padding string "
                                    . "\"$padString\" for one-bit-encoding"
                            ]
                        );
                    }
                    break;

                case 4:
                    if (
                        strspn($padString, '0123456789ABCDEFabcdef')
                            != strlen($padString)
                    ) {
                        throw (new DataValidationFailed())->setMessageContext(
                            [
                                'extraMessage' => "invalid padding string "
                                    . "\"$padString\" for four-bit-encoding"
                            ]
                        );
                    }
                    break;
            }
        }

        if (isset($padType)) {
            if (!in_array($padType, [ STR_PAD_LEFT, STR_PAD_RIGHT ])) {
                /** @throw alcamo::exception::InvalidEnumerator if $padType is
                 *  not a valid padding type. */
                throw (new InvalidEnumerator())->setMessageContext(
                    [
                        'value' => $padType,
                        'expectedOneOf' => [ STR_PAD_LEFT, STR_PAD_RIGHT ]
                    ]
                );
            }
        }

        $this->encoding_ = $encoding;
        $this->bitsPerCharacter_ = $bitsPerCharacter;
        $this->padString_ = $padString;
        $this->padType_ = $padType;
    }

    public function getEncoding(): string
    {
        return $this->encoding_;
    }

    public function getBitsPerCharacter(): int
    {
        return $this->bitsPerCharacter_;
    }

    public function getPadString(): ?string
    {
        return $this->padString_;
    }

    public function getPadType(): ?int
    {
        return $this->padType_;
    }

    /// Pad a string so that it corresponds to an integral number of bytes
    public function align(string $value): string
    {
        if (isset($this->padType_)) {
            switch ($this->bitsPerCharacter_) {
                case 1:
                    return strlen($value) & 7
                        ? str_pad(
                            $value,
                            (strlen($value) + 7) >> 3 << 3,
                            $this->padString_,
                            $this->padType_
                        )
                        : $value;

                case 4:
                    return strlen($value) & 1
                        ? str_pad(
                            $value,
                            (strlen($value) + 1) >> 1 << 1,
                            $this->padString_,
                            $this->padType_
                        )
                        : $value;

                default:
                    return $value;
            }
        } else {
            /** If no padding is specified, throw DataValidationFailed()
             *  unless $value is already aligned. */
            switch ($this->bitsPerCharacter_) {
                case 1:
                    if (strlen($value) & 7) {
                        throw (new DataValidationFailed())->setMessageContext(
                            [
                                'value' => $value,
                                'extraMessage' => 'length not a multiple of 8'
                            ]
                        );
                    }
                    break;

                case 4:
                    if (strlen($value) & 1) {
                        throw (new DataValidationFailed())->setMessageContext(
                            [
                                'value' => $value,
                                'extraMessage' => 'odd length'
                            ]
                        );
                    }
                    break;
            }

            return $value;
        }
    }

    /// Pad a string to a minimum length
    public function pad(string $value, int $minLength): string
    {
        if (isset($this->padType_)) {
            return
                str_pad($value, $minLength, $this->padString_, $this->padType_);
        } else {
            /** @throw alcamo::exception::LengthOutOfRange if shorter than
             *  $minLength and no padding is specified. */
            LengthOutOfRange::throwIfOutside($value, $minLength, null);

            return $value;
        }
    }

    /// Remove padding characters to satisfy a maximum length
    public function unpad(string $value, ?int $maxLength): string
    {
        /** Do nothing if $maxLength is `null` or $value is shorter than
         *  $maxLength. */
        if (!isset($maxLength) || strlen($value) <= $maxLength) {
            return $value;
        }

        if (!isset($this->padType_)) {
            /** @throw alcamo::exception::LengthOutOfRange if longer than
             *  $maxLength and no padding is specified. */
            LengthOutOfRange::throwIfOutside($value, 0, $maxLength);
        }

        $lengthDiff = strlen($value) - $maxLength;

        $padChars = $this->padString_[0];

        /* In Four-bit-strings, use padding characters case-insensitively. */
        if ($this->bitsPerCharacter_ == 4) {
            $padChars = strtoupper($padChars) . strtolower($padChars);
        }

        /** @throw alcamo::exception::SyntaxError if the padding
         *  character is wrong. */
        if ($this->padType_ == STR_PAD_LEFT) {
            if (
                strspn(substr($value, 0, $lengthDiff), $padChars)
                    != $lengthDiff)
            {
                throw (new SyntaxError())->setMessageContext(
                    [
                        'inData' => $value,
                        'extraMessage' => 'invalid left padding data "'
                            . substr($value, 0, $lengthDiff) . '"'
                    ]
                );
            }

            return substr($value, $lengthDiff);
        } else {
            if (
                strspn(substr($value, -$lengthDiff), $padChars) != $lengthDiff)
            {
                throw (new SyntaxError())->setMessageContext(
                    [
                        'inData' => $value,
                        'extraMessage' => 'invalid right padding data "'
                            . substr($value, -$lengthDiff) . '"'
                    ]
                );
            }

            return substr($value, 0, -$lengthDiff);
        }
    }

    /// Truncate to a maximum length
    public function truncate(string $value, int $maxLength): string
    {
        if (!isset($this->padType_)) {
            /** @throw alcamo::exception::LengthOutOfRange if longer than
             *  $maxLength and no padding is specified. */
            LengthOutOfRange::throwIfOutside($value, 0, $maxLength);
        }

        return $this->padType_ == STR_PAD_LEFT
            ? substr($value, -$maxLength)
            : substr($value, 0, $maxLength);
    }
}
