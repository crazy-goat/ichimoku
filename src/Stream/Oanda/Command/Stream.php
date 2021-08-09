<?php
declare(strict_types=1);

namespace CrazyGoat\Forex\Stream\Oanda\Command;

use CrazyGoat\Forex\Service\RC4Crypt;
use CrazyGoat\Forex\Stream\Oanda\DTO\OandaTickPriceCollection;
use CrazyGoat\Forex\ValueObject\TickPriceCollection;
use CrazyGoat\Forex\Writer\RabbitMQ;
use GuzzleHttp\Client;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Stream extends Command
{
    protected static $defaultName = 'forex:stream:oanda';

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = new Client();
        $decoder = new RC4Crypt($this->fetchRC4Key($client));

        $rabbitMQ = RabbitMQ::createFromConfig([]);
        while (true) {
            $last = null;
            foreach ($this->fetchPrices($client, $decoder)->prices() as $tick) {
                $last = $rabbitMQ->write($tick);
            }

            if ($last instanceof AMQPMessage) {
                $rabbitMQ->ack();
            }

            usleep(2000000);
        }

        return Command::SUCCESS;
    }

    /**
     * @param Client $client
     *
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function fetchRC4Key(Client $client): string
    {
        $results = [];
        $response = $client->request('GET', 'https://www1.oanda.com/');
        preg_match('/rc4\-[a-z0-9].*\.js/mU', $response->getBody()->getContents(), $results);

        $keyFile = $results[0] ?? null;
        if ($keyFile === null) {
            throw new \Exception('RC4 Key file not fund');
        }

        $response = $client->request('GET', 'https://www.oanda.com/wandacache/' . $keyFile);
        preg_match('/key=\"(?P<key>[a-z0-9].*)\";/mU', $response->getBody()->getContents(), $results);

        $rc4Key = $results['key'] ?? null;

        if ($rc4Key === null) {
            throw new \Exception('RC4 Key file not fund');
        }

        return $rc4Key;
    }

    /**
     * @param Client   $client
     * @param RC4Crypt $decoder
     *
     * @return TickPriceCollection
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function fetchPrices(Client $client, RC4Crypt $decoder): TickPriceCollection
    {
        $response = $client->request('GET', 'https://www.oanda.com/lfr/rates_all?_=' . date('U'));
        $data = ($decoder->decrypt($response->getBody()->getContents()));

        return OandaTickPriceCollection::fromString($data);
    }
}