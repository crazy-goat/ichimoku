<?php

namespace CrazyGoat\Forex\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TickData
 *
 * @ORM\Table(name="tick_data")
 * @ORM\Entity
 */
class TickData
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
     * @ORM\Column(name="bid", type="decimal", precision=20, scale=6, nullable=false)
     */
    private $bid;

    /**
     * @var string
     *
     * @ORM\Column(name="ask", type="decimal", precision=20, scale=6, nullable=false)
     */
    private $ask;


}
