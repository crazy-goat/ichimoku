<?php

namespace CrazyGoat\Forex\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CandleData
 *
 * @ORM\Table(name="candle_data")
 * @ORM\Entity
 */
class CandleData
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
     * @var string
     *
     * @ORM\Column(name="open", type="decimal", precision=20, scale=6, nullable=false)
     */
    private $open;

    /**
     * @var string
     *
     * @ORM\Column(name="high", type="decimal", precision=20, scale=6, nullable=false)
     */
    private $high;

    /**
     * @var string
     *
     * @ORM\Column(name="low", type="decimal", precision=20, scale=6, nullable=false)
     */
    private $low;

    /**
     * @var string
     *
     * @ORM\Column(name="close", type="decimal", precision=20, scale=6, nullable=false)
     */
    private $close;


}
