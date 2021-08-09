<?php

namespace CrazyGoat\Forex\ValueObject;

class Window
{
    private int $size;

    private array $data;

    public function __construct(int $size)
    {
        $this->size = $size;
        $this->data = [];
    }

    public function append($value)
    {
        $this->data[] = $value;

        if (count($this->data) > $this->size) {
            return array_shift($this->data);
        }

        return null;
    }

    public function full(): bool
    {
        return count($this->data) === $this->size;
    }

    public function list(): array
    {
        return $this->data;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function count(): int
    {
        return count($this->data);
    }
}