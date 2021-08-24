<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\ValueObject;

class IchimokuDataCollection
{
    /** @var IchimokuData[] */
    private array $data;

    public function __construct(?IchimokuData ...$data)
    {
        $this->data = array_values($data);
    }

    /**
     * @return IchimokuData[]
     */
    public function list(): array
    {
        return $this->data;
    }

    public function first(): IchimokuData
    {
        return reset($this->data);
    }

    public function last(): IchimokuData
    {
        return end($this->data);
    }

    public function getByOffset(int $offset): ?IchimokuData
    {
        return $this->data[$offset] ?? null;
    }

    public function splice(int $n): array
    {
        return array_splice($this->data, $n);
    }

    public function count(): int
    {
        return count($this->data);
    }
}