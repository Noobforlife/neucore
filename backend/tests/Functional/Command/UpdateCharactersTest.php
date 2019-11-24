<?php
/** @noinspection DuplicatedCode */

declare(strict_types=1);

namespace Tests\Functional\Command;

use Neucore\Command\UpdateCharacters;
use Neucore\Entity\Character;
use Neucore\Factory\RepositoryFactory;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Tests\Client;
use Tests\Functional\ConsoleTestCase;
use Tests\Helper;
use Tests\Logger;

class UpdateCharactersTest extends ConsoleTestCase
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var Client
     */
    private $client;

    protected function setUp(): void
    {
        $this->helper = new Helper();
        $this->helper->emptyDb();
        $this->em = $this->helper->getEm();

        $this->log = new Logger('Test');
        $this->client = new Client();
    }

    public function testExecuteErrorUpdateChar()
    {
        $c = (new Character())->setId(1)->setName('c1');
        $this->helper->addNewPlayerToCharacterAndFlush($c);
        $this->client->setResponse(new Response(500), new Response(500));

        $output = $this->runConsoleApp('update-chars', ['--sleep' => 0], [
            ClientInterface::class => $this->client,
            LoggerInterface::class => $this->log
        ]);

        $actual = explode("\n", $output);
        $this->assertSame(4, count($actual));
        $this->assertStringEndsWith('Started "update-chars"', $actual[0]);
        $this->assertStringEndsWith('  Character 1: ' . UpdateCharacters::UPDATE_NOK, $actual[1]);
        $this->assertStringEndsWith('Finished "update-chars"', $actual[2]);
        $this->assertStringEndsWith('', $actual[3]);
    }

    public function testExecuteUpdateCharInvalidCorp()
    {
        $c = (new Character())->setId(1)->setName('c1');
        $this->helper->addNewPlayerToCharacterAndFlush($c);
        $this->client->setResponse(
            new Response(200, [], '[{"id": 1, "name": "c1", "category": "character"}]'), // postUniverseNames
            new Response(200, [], '[{"character_id": 1, "corporation_id": 0}]') // postCharactersAffiliation
        );

        $output = $this->runConsoleApp('update-chars', ['--sleep' => 0], [
            ClientInterface::class => $this->client,
            LoggerInterface::class => $this->log
        ]);

        $actual = explode("\n", $output);
        $this->assertSame(5, count($actual));
        $this->assertStringEndsWith('Started "update-chars"', $actual[0]);
        $this->assertStringEndsWith('  Characters 1: ' . UpdateCharacters::UPDATE_OK, $actual[1]);
        $this->assertStringEndsWith('  Corporation 0: ' . UpdateCharacters::UPDATE_NOK, $actual[2]);
        $this->assertStringEndsWith('Finished "update-chars"', $actual[3]);
        $this->assertStringEndsWith('', $actual[4]);
    }

    public function testExecuteWithAlliance()
    {
        $c = (new Character())->setId(3)->setName('char1');
        $this->helper->addNewPlayerToCharacterAndFlush($c);
        $this->helper->addCharacterMain('char2', 6);

        $this->client->setResponse(
            new Response(200, [], '[
                {"id": 3, "name": "char1", "category": "character"},
                {"id": 6, "name": "char2", "category": "character"}
            ]'), // postUniverseNames
            new Response(200, [], '[
                {"character_id": 3, "corporation_id": 101},
                {"character_id": 6, "corporation_id": 101}
            ]'), // postCharactersAffiliation
            new Response(200, [], '{"name": "corp1", "ticker": "t", "alliance_id": 212}'),
            new Response(200, [], '{"name": "The Alli.", "ticker": "-A-"}')
        );

        $output = $this->runConsoleApp('update-chars', ['--sleep' => 0], [
            ClientInterface::class => $this->client,
        ]);

        $actual = explode("\n", $output);
        $this->assertSame(6, count($actual));
        $this->assertStringEndsWith('Started "update-chars"', $actual[0]);
        $this->assertStringEndsWith('  Characters 3,6: ' . UpdateCharacters::UPDATE_OK, $actual[1]);
        $this->assertStringEndsWith('  Corporation 101: ' . UpdateCharacters::UPDATE_OK, $actual[2]);
        $this->assertStringEndsWith('  Alliance 212: ' . UpdateCharacters::UPDATE_OK, $actual[3]);
        $this->assertStringEndsWith('Finished "update-chars"', $actual[4]);
        $this->assertStringEndsWith('', $actual[5]);

        // read result
        $this->em->clear();

        $repositoryFactory = new RepositoryFactory($this->em);

        $actualChars = $repositoryFactory->getCharacterRepository()->findBy([]);
        $this->assertSame(2, count($actualChars));
        $this->assertSame(3, $actualChars[0]->getId());
        $this->assertSame(6, $actualChars[1]->getId());
        $this->assertNotNull($actualChars[0]->getLastUpdate());
        $this->assertNotNull($actualChars[1]->getLastUpdate());
        $this->assertSame(101, $actualChars[0]->getCorporation()->getId());
        $this->assertSame(101, $actualChars[1]->getCorporation()->getId());
        $this->assertSame(212, $actualChars[0]->getCorporation()->getAlliance()->getId());
        $this->assertSame(212, $actualChars[1]->getCorporation()->getAlliance()->getId());

        $actualCorps = $repositoryFactory->getCorporationRepository()->findBy([]);
        $this->assertSame(1, count($actualCorps));
        $this->assertSame(101, $actualCorps[0]->getId());

        $actualAlliances = $repositoryFactory->getAllianceRepository()->findBy([]);
        $this->assertSame(1, count($actualAlliances));
        $this->assertSame(212, $actualAlliances[0]->getId());
    }
}
