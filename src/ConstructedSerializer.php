<?php

namespace alcamo\data_element;

use alcamo\collection\ReadonlyCollectionTrait;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\exception\{DataValidationFailed, Eof, InvalidType, SyntaxError};
use alcamo\input_stream\StringInputStream;
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\{HexBinaryLiteral, LiteralInterface};

/**
 * @brief (De)Serializer for constructed data
 *
 * @date Last reviewed 2026-03-02
 */
class ConstructedSerializer extends AbstractSerializer implements
    \Countable,
    \ArrayAccess,
    \Iterator
{
    use ReadonlyCollectionTrait;

    public const SUPPORTED_DATATYPE_XNAMES = [ self::XSD_NS . ' string' ];

    /**
     * @copydoc alcamo::data_element::AbstractSerializer::ENCODINGS
     *
     * Here the padding string for DUMP is nonempyt to allow for the
     * TRUNCATE_SILENTLY flag. It has no other effect.
     */
    public const ENCODINGS = [
        'BINARY' => [ 8, "\x00" ],
        'DUMP'   => [ 8, ' ' ]
    ];

    public static function newFromProps($props): SerializerInterface
    {
        $props = (object)$props;

        return new static(
            $props->serializers ?? null,
            $props->separator ?? null,
            $props->lengthRange ?? null,
            $props->flags ?? null
        );
    }

    private $separator_; ///< ?string

    /**
     * @parm $serializers Iterable of SerializerInterface objects
     *
     * @parm $separator String to separate items in
     * (de)serialization. [default one space for `DUMP` encoding, otherwise
     * empty string]
     *
     * @param $lengthRange NonNegativeRange|array Allowed length of serialized
     * data, in bytes or nibbles. If given a an array, it must have 1 to 2
     * items representing the minimum and optionlly the maximim length.
     *
     * @param $flags Bitwise-OR-combination of the constants in
     * alcamo::data_element::SerializerInterface.
     *
     * Padding string and padding type are taken from the last serializer.
     */
    public function __construct(
        iterable $serializers,
        ?string $separator = null,
        $lengthRange = null,
        ?int $flags = null
    ) {
        foreach ($serializers as $key => $serializer) {
            if (!($serializer instanceof SerializerInterface)) {
                /** @throw alcamo::exception::InvalidType if an item in
                 *  $serializers is not a SerializerInterface object. */
                throw (new InvalidType())->setMessageContext(
                    [
                        'value' => $serializer,
                        'expectedOneOf' => SerializerInterface::class
                    ]
                );
            }

            $this->data_[$key] = $serializer;
        }

        parent::__construct(
            self::XSD_NS . ' string',
            array_key_first(static::ENCODINGS),
            $lengthRange,
            $flags,
            $serializer->padString_,
            $serializer->padType_,
            $serializer->literalWorkbench_
        );

        if (!isset($separator) && $this->encoding_ == 'DUMP') {
            $separator = ' ';
        }

        $this->separator_ = $separator;
    }

    public function serialize(LiteralInterface $literal): string
    {
        if (!($literal instanceof ConstructedLiteral)) {
            /** @throw alcamo::exception::InvalidType if $literal is not
             *  ConstructedLiteral. */
            throw (new InvalidType())->setMessageContext(
                [
                    'type' => get_class($literal),
                    'extraMessage' => 'incompatible with ' . static::class
                ]
            );
        }

        if (!($this->flags_ & self::TRUNCATE_SILENTLY)) {
            if (count($literal) != count($this)) {
                /** @todo throw alcamo::exception::DataValidationFailed if
                 *  literal count does not match serializer count and the
                 *  TRUNCATE_SILENTLY flag is not set. */
                throw (new DataValidationFailed())->setMessageContext(
                    [
                        'extraMessage' => 'literal count ' . count($literal)
                            . ' does not match serializer count '
                            . count($this)
                    ]
                );
            }
        }

        $this->rewind();

        foreach ($literal as $item) {
            if (isset($result)) {
                $result .= $this->separator_
                    . (isset($item) ? $this->current()->serialize($item) : '');
            } else {
                $result =
                    isset($item) ? $this->current()->serialize($item) : '';
            }

            $this->next();

            if (!$this->valid()) {
                break;
            }
        }

        /** For `DUMP` encoding, surround the result by separator and
         *  brackets. */
        return $this->encoding_ == 'DUMP'
            ? return "[{$this->separator_}$result{$this->separator_}]"
            : $this->adjustOutputLength($result);
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        if ($this->encoding_ == 'DUMP') {
            return $this->deserializeDump($input, $datatype);
        }

        $this->validateInputLength($input);

        $result = [];
        $pos = 0;

        /**
         * If the input ends exactly after application of a deserializer and
         * $flags contain TRUNCATE_SILENTLY, accept this gracefully.
         *
         * @throw alcamo::exception::Eof if input ends before all
         * deserializers have been applied and either $flags do not contain
         * TRUNCATE_SILENTLY or there are data left ot read.
         */
        if (isset($this->separator_)) {
            /** If there is a separator defined, read up to the separator for
             *  each field and apply a deserializer. */
            foreach ($this as $key => $serializer) {
                if (!isset($input[$pos])) {
                    if ($this->flags_ & self::TRUNCATE_SILENTLY) {
                        break;
                    }

                    throw (new Eof('Failed to read from {object}'))
                        ->setMessageContext(
                            [
                                'object' => $input,
                                'atOffset' => $pos,
                                'forKey' => $key
                            ]
                        );
                }

                $pos2 = strpos($input, $this->separator_, $pos);

                $length = $pos2 === false
                    ? strlen($input) - $pos
                    : $pos2 - $pos;

                $result[] = $length
                    ? $serializer->deserialize(substr($input, $pos, $length))
                    : null;

                $pos += $length + strlen($this->separator_);
            }
        } else {
            /** If there is no separator defined, read the minimum length for
             *  each deserializer. */
            foreach ($this as $key => $serializer) {
                if (
                    !isset($input[$pos])
                        && $this->flags_ & self::TRUNCATE_SILENTLY
                ) {
                    break;
                }

                $length = $serializer->getLengthRange()->getMin();

                if (strlen($input) < $pos + $length) {
                    throw (new Eof())->setMessageContext(
                        [
                            'object' => $input,
                            'requestedUnits' => $length,
                            'atOffset' => $pos,
                            'forKey' => $key
                        ]
                    );
                }

                $result[] =
                    $serializer->deserialize(substr($input, $pos, $length));

                $pos += $length;
            }
        }

        if (
            $pos < strlen($input) && !($this->flags_ & self::TRUNCATE_SILENTLY)
        ) {
            /** @throw alcamo::exception::SyntaxError if there is input left
             * after all deserializers have been applied and $flags do not
             * contain TRUNCATE_SILENTLY. */
            throw (new SyntaxError())->setMessageContext(
                [
                    'inData' => $input,
                    'atOffset' => $pos,
                    'extraMessage' => 'spurious trailing data'
                ]
            );
        }

        return new Constructedliteral($result, $datatype);
    }

    protected function deserializeDump(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        $separatorLen = strlen($this->separator_);
        $surroundLen = strlen($separatorLen) + 1;

        if (
            substr($input, 0, $surroundLen) != "[{$this->separator_}"
                || substr($input, 0, -$surroundLen) != "{$this->separator_}]"
        ) {
            /** @throw alcamo::exception::SyntaxError on attempt to
             *  deserialize an input which is not surrounded by a separator
             *  and brackets. */
            throw (new SyntaxError())->setMessageContext(
                [
                    'inData' => $input,
                    'extraMessage' => "not surrounded by "
                        . "\"[{$this->separator_}\" and "
                        . "\"{$this->separator_}]\""
                ]
            );
        }

        /* Data without left surrounding and without right bracket but with
         * right separator, so that the last item needs no special
         * handling. */
        $stream = new StringInputStream(
            substr($input, $surroundLen, strlen($input) - $surroundLen - 1)
        );

        $result = [];

        foreach ($this as $key => $serializer) {
             /* This splits the stream syntactically into items regardless of
             * the item's serializers. The deserialize() below will check
             * whether the item syntax matches the expectation of the
             * serializer. */
            $item = $stream->extractToken($this->separator_);

            if (!isset($item)) {
                break;
            }

            $result[] = $serializer->deserialize($item);

            $separator = $stream->extract($separatorLen);

            if (!isset($separator)) {
                break;
            } elseif ($separator != $this->separator_) {
                /** @throw alcamo::exception::SyntaxError on attempt to
                 *  deserialize an input where a separator is wrong. */
                throw (new SyntaxError())->setMessageContext(
                    [
                        'inData' => $input,
                        'atOffset' => $surroundLen + $stream->getOffset()
                            - $separatorLen,
                        'extraMessage' => "expected separator "
                            . "\"{$this->separator_}\", found \"$separator\"",
                        'forKey' => $key
                    ]
                );
            }
        }

        if (!($this->flags_ & self::TRUNCATE_SILENTLY)) {
            if ($stream->isGood()) {
                /** @throw alcamo::exception::SyntaxError if there is input
                 * left after all deserializers have been applied and $flags
                 * do not contain TRUNCATE_SILENTLY. */
                throw (new SyntaxError())->setMessageContext(
                    [
                        'inData' => $input,
                        'atOffset' => $surroundLen + $stream->getOffset(),
                        'extraMessage' => 'spurious trailing data'
                        ]
                );
            }

            if (count($result) < count($this)) {
                /** @throw alcamo::exception::Eof if input ends before all
                 * deserializers have been applied and $flags do not contain
                 * TRUNCATE_SILENTLY. */

                throw (new Eof('Failed to read from {object}'))
                    ->setMessageContext(
                        [
                            'object' => $input,
                            'atOffset' => $surroundLen + $stream->getOffset(),
                            'forKey' => $key
                        ]
                    );
            }
        }

        return new Constructedliteral($result, $datatype);
    }
}
