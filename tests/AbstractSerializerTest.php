<?php

namespace alcamo\data_element;

use alcamo\range\NonNegativeRange;
use PHPUnit\Framework\TestCase;

class AbstractSerializerTest extends TestCase
{
    public function testCreateFromProps(): void
    {
        $serializer = AbstractSerializer::createFromProps(
            [
                'class' => BinarySerializer::class,
                'lengthRange' => [ 42, 43 ]
            ]
        );

        $this->assertInstanceOf(BinarySerializer::class, $serializer);

        $this->assertEquals(
            new NonNegativeRange(42, 43),
            $serializer->getLengthRange()
        );
    }
}
