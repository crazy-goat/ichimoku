<?php

namespace CrazyGoat\Forex\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CandleData
 *
 * @ORM\Table(name="ichimoku_data")
 * @ORM\Entity
 */
class IchimokuData
{
    /**
     * @var string
     *
     * @ORM\Column(name="symbol", type="string", length=16, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $symbol;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time", type="datetime", precision=6, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $time;

    /**
     * @var string
     *
     * @ORM\Column(name="period", type="string", length=2, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $period;

    /**
     * @var float
     *
     * @ORM\Column(name="tenkan", type="decimal", precision=20, scale=6, nullable=true)
     */
    private $tenkan;

    /**
     * @var float
     *
     * @ORM\Column(name="kijun", type="decimal", precision=20, scale=6, nullable=true)
     */
    private $kijun;

    /**
     * @var float
     *
     * @ORM\Column(name="span_a", type="decimal", precision=20, scale=6, nullable=true)
     */
    private $spanA;

    /**
     * @var float
     *
     * @ORM\Column(name="span_b", type="decimal", precision=20, scale=6, nullable=true)
     */
    private $spanB;

    /**
     * @var float
     *
     * @ORM\Column(name="chikou", type="decimal", precision=20, scale=6, nullable=true)
     */
    private $chikou;
}
