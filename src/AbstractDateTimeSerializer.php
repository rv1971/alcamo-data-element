<?php

namespace alcamo\data_element;

use alcamo\range\NonNegativeRange;
use alcamo\rdfa\LiteralInterface;

abstract class AbstractDateTimeSerializer extends AbstractSerializerWithEncoding
{
    public const SUPPORTED_ENCODINGS = [ 'ASCII', 'BCD', 'EBCDIC' ];

    public const DEFAULT_ENCODING = 'ASCII';

    public const DEFAULT_POSIX_FORMATS = [];

    /// Map of Posix format specifiers to text of appropriate length
    public const POSIX_FORMAT_SPECS_TO_TEXT = [
        '%Y' => 'YYYY',
        '%m' => 'mm',
        '%y' => 'yy',
        '%d' => 'dd',
        '%H' => 'HH',
        '%M' => 'ii',
        '%S' => 'ss'
    ];

    /// Map of Posix format specifiers to PHP format specifiers
    public const POSIX_FORMAT_SPECS_TO_PHP_FORMAT_SPECS = [
        '%Y' => 'Y',
        '%m' => 'm',
        '%y' => 'y',
        '%d' => 'd',
        '%H' => 'H',
        '%M' => 'i',
        '%S' => 's'
    ];

    private $posixFormat_; ///< string
    private $phpFormat_;   ///< string

    public function __construct(
        ?DataElementInterface $dataElement = null,
        ?string $posixFormat = null,
        ?int $flags = null,
        ?string $encoding = null
    ) {
        if (isset($encoding)) {
            $encoding = static::DEFAULT_ENCODING;
        }

        $this->posixFormat_ = $posixFormat
            ?? static::DEFAULT_POSIX_FORMATS[$encoding]
            ?? static::DEFAULT_POSIX_FORMATS['*'];

        $this->phpFormat_ = strtr(
            $this->posixFormat_,
            static::POSIX_FORMAT_SPECS_TO_PHP_FORMAT_SPECS
        );

        $length = strlen(
            strtr($this->posixFormat_, static::POSIX_FORMAT_SPECS_TO_TEXT)
        );

        parent::__construct(
            $dataElement,
            new NonNegativeRange($length, $length),
            $flags,
            $encoding
        );
    }

    public function serialize(LiteralInterface): string
    {
        $this->validateLiteralClass($literal);

        $value = $literal->format($this->phpFormat_);

        switch ($this->encoding_) {
            case 'ASCII':
                return $value;

            case 'BCD':
                OutOfRange::throwIfNegative($literal->format('Y'));

                return hex2bin($value);

            case 'EBCDIC':
                return strtr(
                    $value,
                    '-0123456789:T',
                    "\x60\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9\x7A\xE3"
                );
        }
    }

    public function deserialize(string $input): LiteralInterface
    {
        if ($this->encoding_ == 'BCD') {
            $input = bin2hex($input);
        }

        $this->validateInputLength($input);

        switch ($this->encoding_) {
            case 'ASCII':
                $value = $input;
                break;

            case 'BCD':
                $value = bin2hex($input);
                break;

            case 'EBCDIC':
                $value = strtr(
                    $input,
                    "\x60\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9\x7A\xE3",
                    '-0123456789:T'
                );
                break;
        }

        $literalClass = static::SUPPORTED_LITERAL_CLASSES[0];

        return new $literalClass(
            DateTime::createFromFormat($this->phpFormat_, $value),
            $this->dataElement_->getDatatype()->getUri()
        );
    }
}
