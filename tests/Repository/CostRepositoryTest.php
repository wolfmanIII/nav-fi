<?php

namespace App\Tests\Repository;

use App\Entity\Asset;
use App\Entity\Campaign;
use App\Entity\Cost;
use App\Entity\CostCategory;
use App\Entity\User;
use App\Repository\CostRepository;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CostRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private CostRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $dbPath = dirname(__DIR__, 2) . '/var/test_cost_repo.db';
        if (is_file($dbPath)) {
            unlink($dbPath);
        }

        $_SERVER['DATABASE_URL'] = 'sqlite:///' . $dbPath;
        $_ENV['DATABASE_URL'] = $_SERVER['DATABASE_URL'];

        $this->em = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->em->getRepository(Cost::class);

        $connection = $this->em->getConnection();
        if (!Type::hasType('uuid')) {
            Type::addType('uuid', StringType::class);
        }
        $connection->getDatabasePlatform()->registerDoctrineTypeMapping('uuid', 'string');

        $schemaTool = new SchemaTool($this->em);
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($this->em->getMetadataFactory()->getAllMetadata());
    }

    public function testFindForUserWithFilters(): void
    {
        // 1. Setup Utente e Campagna
        $user = new User();
        $user->setEmail('test@cost.com');
        $user->setPassword('hash');
        $this->em->persist($user);

        $campaign = new Campaign();
        $campaign->setTitle('Campaign A');
        $campaign->setSessionDay(100);
        $campaign->setSessionYear(1105);
        $this->em->persist($campaign);

        $assetA = new Asset();
        $assetA->setUser($user);
        $assetA->setName('Asset A');
        $assetA->setCampaign($campaign);
        $this->em->persist($assetA);

        $assetB = new Asset();
        $assetB->setUser($user);
        $assetB->setName('Asset B'); // Nessuna campagna o diversa
        $this->em->persist($assetB);

        $catFuel = new CostCategory();
        $catFuel->setCode('FUEL');
        $catFuel->setDescription('Fuel');
        $this->em->persist($catFuel);

        $catRepair = new CostCategory();
        $catRepair->setCode('REPAIR');
        $catRepair->setDescription('Repairs');
        $this->em->persist($catRepair);

        // 2. Crea costi
        $cost1 = new Cost();
        $cost1->setTitle('Fuel Purchase');
        $cost1->setUser($user);
        $cost1->setAsset($assetA);
        $cost1->setCostCategory($catFuel);
        $cost1->setAmount('100.00');
        $this->em->persist($cost1);

        $cost2 = new Cost();
        $cost2->setTitle('Engine Repair');
        $cost2->setUser($user);
        $cost2->setAsset($assetB);
        $cost2->setCostCategory($catRepair);
        $cost2->setAmount('500.00');
        $this->em->persist($cost2);

        $this->em->flush();

        // 3. Test filtri

        // Filtra per titolo
        $res = $this->repository->findForUserWithFilters($user, ['title' => 'Fuel', 'category' => null, 'asset' => null, 'campaign' => null], 1, 10);
        self::assertCount(1, $res['items']);
        self::assertSame('Fuel Purchase', $res['items'][0]->getTitle());

        // Filtra per categoria
        $res = $this->repository->findForUserWithFilters($user, ['title' => null, 'category' => $catRepair->getId(), 'asset' => null, 'campaign' => null], 1, 10);
        self::assertCount(1, $res['items']);
        self::assertSame('Engine Repair', $res['items'][0]->getTitle());

        // Filtra per asset
        $res = $this->repository->findForUserWithFilters($user, ['title' => null, 'category' => null, 'asset' => $assetA->getId(), 'campaign' => null], 1, 10);
        self::assertCount(1, $res['items']);
        self::assertSame('Fuel Purchase', $res['items'][0]->getTitle());

        // Filtra per campagna
        $res = $this->repository->findForUserWithFilters($user, ['title' => null, 'category' => null, 'asset' => null, 'campaign' => $campaign->getId()], 1, 10);
        self::assertCount(1, $res['items']);
        self::assertSame('Fuel Purchase', $res['items'][0]->getTitle()); // Solo AssetA è nella Campagna A
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}
