<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\ValueObject;

use CrazyGoat\Forex\Indicator\SimpleIndicatorAbstract;

class IndicatorCollection
{
    /** @var array<string, SimpleIndicatorAbstract> */
    private array $indicators;

    /** @var array<string, Window> */
    private array $history;

    public function __construct(int $size, NamedIndicator ...$indicators)
    {
        foreach ($indicators as $indicator) {
            $this->indicators[$indicator->name()] = $indicator->indicator();
            $this->history[$indicator->name()] = new Window($size);
        }
    }

    public function append(Candle $candle) {
        foreach ($this->indicators as $name => $indicator) {
            $indicator->append($candle);
            $this->history[$name]->append($indicator->calculate());
        }
    }

    public function ready(): bool
    {
        foreach ($this->indicators as $indicator) {
            if (!$indicator->ready()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $name
     * @param int    $offset
     *
     * @return float|MultiPrice
     * @throws \Exception
     */
    public function value(string $name, int $offset = 0)
    {
        $indicator = $this->indicators[$name] ?? null;

        if ($indicator instanceof SimpleIndicatorAbstract) {
            $history = $this->history[$name];
            return $history->list()[$history->size() - $offset -1] ?? null;
        }

        throw new \Exception('Indicator not found');
    }

    public function history(string $name): array
    {
        $indicator = $this->indicators[$name] ?? null;

        if ($indicator instanceof SimpleIndicatorAbstract) {
            return $this->history[$name]->list();
        }
        throw new \Exception('Indicator not found');
    }
}