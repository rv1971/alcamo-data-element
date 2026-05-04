<?php

namespace alcamo\data_element;

use alcamo\collection\ReadonlyCollectionTrait;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\exception\{DataValidationFailed, Eof, InvalidType, SyntaxError};
use alcamo\input_stream\StringInputStream;
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\{ConstructedLiteral, HexBinaryLiteral, LiteralInterface};

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
     * (de)serialization. [default one space in serialization and any
     * whitespace in deserialization for `DUMP` encoding, otherwise empty
     * string]
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

        $this->separator_ = $separator;
    }

    public function getSeparator(): ?string
    {
        return $this->separator_;
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

        if ($this->encoding_ == 'DUMP') {
            return $this->dump($literal);
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

        return $this->adjustOutputLength($result);
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        if ($this->encoding_ == 'DUMP') {
            return $this->deDump($input, $datatype);
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

                $result[] =
                    $serializer->deserialize(substr($input, $pos, $length));

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

    public function dump(LiteralInterface $literal): string
    {
        $separator = $this->separator_ ?? ' ';

        $this->rewind();

        foreach ($literal as $item) {
            if (isset($result)) {
                $result .= $separator
                    . (isset($item) ? $this->current()->dump($item) : '');
            } else {
                $result = isset($item) ? $this->current()->dump($item) : '';
            }

            $this->next();

            if (!$this->valid()) {
                break;
            }
        }

        /** Surround the result by brackets. If the separator is a space,
         *  insert spaces between brackets and content. */
        return $separator == ' ' ? "[ $result ]" : "[$result]";
    }

    public function dedump(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        $separatorLen = strlen($this->separator_);
        $surroundLen = strlen($separatorLen) + 1;

        if ($input[0] != '[' || $input[-1] != ']') {
            /** @throw alcamo::exception::SyntaxError on attempt to
             *  dedump an input which is not surrounded by brackets. */
            throw (new SyntaxError())->setMessageContext(
                [
                    'inData' => $input,
                    'extraMessage' => "not surrounded by \"[\" and \"]\""
                ]
            );
        }

        /* Data without brackets. */
        $stream = new StringInputStream(substr($input, 1, strlen($input) - 2));

        /* If separator is whitespace, skip optional whitespace after opening
         * bracket. */
        if (!isset($this->separator_)) {
            $stream->extractWs();
        }

        $result = [];

        foreach ($this as $key => $serializer) {
             /* This splits the stream syntactically into items regardless of
             * the item's serializers. The dedump($item) call will check
             * whether the item syntax matches the expectation of the
             * serializer. */
            $item = $stream->extractToken($this->separator_, true);

            if (!isset($item)) {
                break;
            }

            $result[] = $serializer->dedump($item);
        }

        if (!($this->flags_ & self::TRUNCATE_SILENTLY)) {
            if ($stream->isGood()) {
                /** @throw alcamo::exception::SyntaxError if there is input
                 * left after all deserializers have been applied and $flags
                 * do not contain TRUNCATE_SILENTLY. */
                throw (new SyntaxError())->setMessageContext(
                    [
                        'inData' => $input,
                        'atOffset' => 1 + $stream->getOffset(),
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
                            'atOffset' => 1 + $stream->getOffset(),
                            'forKey' => $key
                        ]
                    );
            }
        }

        return new Constructedliteral($result, $datatype);
    }
}
