<?php

namespace App\Tests\Repository;

use App\Entity\Asset;
use App\Entity\Campaign;
use App\Entity\Mortgage;
use App\Entity\User;
use App\Repository\MortgageRepository;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MortgageRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private MortgageRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $dbPath = dirname(__DIR__, 2) . '/var/test_mortgage_repo.db';
        if (is_file($dbPath)) {
            unlink($dbPath);
        }

        $_SERVER['DATABASE_URL'] = 'sqlite:///' . $dbPath;
        $_ENV['DATABASE_URL'] = $_SERVER['DATABASE_URL'];

        $this->em = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->em->getRepository(Mortgage::class);

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
        // 1. Setup User & Common Entities
        $user = new User();
        $user->setEmail('banker@mortgage.com');
        $user->setPassword('hash');
        $this->em->persist($user);

        $campaign = new Campaign();
        $campaign->setTitle('Campaign X');
        $campaign->setSessionDay(100);
        $campaign->setSessionYear(1105);
        $this->em->persist($campaign);

        $assetA = new Asset();
        $assetA->setUser($user);
        $assetA->setName('Ship A');
        $assetA->setCampaign($campaign);
        $this->em->persist($assetA);

        $assetB = new Asset();
        $assetB->setUser($user);
        $assetB->setName('Ship B');
        $this->em->persist($assetB);

        $rate = new \App\Entity\InterestRate();
        // $rate->setName('Standard'); // Field does not exist
        $rate->setPriceMultiplier('1.0');
        $rate->setPriceDivider(1);
        $rate->setDuration(240);
        $rate->setAnnualInterestRate('5.00');
        $this->em->persist($rate);

        // 2. Create Mortgages
        $mortgage1 = new Mortgage();
        $mortgage1->setName('MOR - Ship A');
        $mortgage1->setUser($user);
        $mortgage1->setAsset($assetA);
        $mortgage1->setInterestRate($rate);
        $this->em->persist($mortgage1);

        $mortgage2 = new Mortgage();
        $mortgage2->setName('MOR - Ship B');
        $mortgage2->setUser($user);
        $mortgage2->setAsset($assetB);
        $mortgage2->setInterestRate($rate);
        $this->em->persist($mortgage2);

        $this->em->flush();

        // 3. Test Filters

        // Filter by Name
        $res = $this->repository->findForUserWithFilters($user, ['name' => 'Ship A', 'asset' => null, 'campaign' => null], 1, 10);
        self::assertCount(1, $res['items']);
        self::assertSame('MOR - Ship A', $res['items'][0]->getName());

        // Filter by Asset
        $res = $this->repository->findForUserWithFilters($user, ['name' => null, 'asset' => $assetB->getId(), 'campaign' => null], 1, 10);
        self::assertCount(1, $res['items']);
        self::assertSame('MOR - Ship B', $res['items'][0]->getName());

        // Filter by Campaign
        $res = $this->repository->findForUserWithFilters($user, ['name' => null, 'asset' => null, 'campaign' => $campaign->getId()], 1, 10);
        self::assertCount(1, $res['items']);
        self::assertSame('MOR - Ship A', $res['items'][0]->getName()); // Only A is in Campaign X
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}
