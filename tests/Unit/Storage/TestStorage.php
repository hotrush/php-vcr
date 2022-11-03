<?php

declare(strict_types=1);

namespace VCR\Tests\Unit\Storage;

use VCR\Storage\AbstractStorage;

class TestStorage extends AbstractStorage
{
    /** @var array<mixed> */
    private $recording;

    public function storeRecording(array $recording): void
    {
        $this->recording = $recording;
    }

    public function next(): void
    {
    }

    public function valid(): bool
    {
        return (bool) $this->position;
    }

    public function rewind(): void
    {
        reset($this->recording);
    }
}
