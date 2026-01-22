<?php

namespace App\Tests\Repository;

use App\Entity\Asset;
use App\Entity\Campaign;
use App\Entity\Income;
use App\Entity\User;
use App\Repository\IncomeRepository;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class IncomeRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private IncomeRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $dbPath = dirname(__DIR__, 2) . '/var/test_repo.db';
        if (is_file($dbPath)) {
            unlink($dbPath);
        }

        $_SERVER['DATABASE_URL'] = 'sqlite:///' . $dbPath;
        $_ENV['DATABASE_URL'] = $_SERVER['DATABASE_URL'];

        $this->em = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->em->getRepository(Income::class);

        $connection = $this->em->getConnection();
        if (!Type::hasType('uuid')) {
            Type::addType('uuid', StringType::class);
        }
        $connection->getDatabasePlatform()->registerDoctrineTypeMapping('uuid', 'string');

        $schemaTool = new SchemaTool($this->em);
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($this->em->getMetadataFactory()->getAllMetadata());
    }

    public function testFindAllNotCanceledForUserExcludesCancelledIncome(): void
    {
        // 1. Setup Utente e Campagna
        $user = new User();
        $user->setEmail('test@repo.com');
        $user->setPassword('hash');
        $this->em->persist($user);

        $campaign = new Campaign();
        $campaign->setTitle('Repo Campaign');
        $campaign->setSessionDay(100);
        $campaign->setSessionYear(1105);
        $this->em->persist($campaign);

        $asset = new Asset();
        $asset->setUser($user);
        $asset->setName('Repo Asset');
        $asset->setCampaign($campaign);
        $this->em->persist($asset);

        $category = new \App\Entity\IncomeCategory();
        $category->setCode('TEST');
        $category->setDescription('Test Category');
        $this->em->persist($category);

        // 2. Crea Income attivo
        $activeIncome = new Income();
        $activeIncome->setTitle('Active Job');
        $activeIncome->setUser($user);
        $activeIncome->setAsset($asset);
        $activeIncome->setSigningDay(100);
        $activeIncome->setSigningYear(1105);
        $activeIncome->setIncomeCategory($category);
        $activeIncome->setAmount('5000.00');
        $this->em->persist($activeIncome);

        // 3. Crea Income cancellato
        $cancelledIncome = new Income();
        $cancelledIncome->setTitle('Cancelled Job');
        $cancelledIncome->setUser($user);
        $cancelledIncome->setAsset($asset);
        $cancelledIncome->setSigningDay(90);
        $cancelledIncome->setSigningYear(1105);
        $cancelledIncome->setCancelDay(95);
        $cancelledIncome->setCancelYear(1105);
        $cancelledIncome->setIncomeCategory($category);
        $cancelledIncome->setAmount('1000.00');
        // Nota: il repository si basa su cancelDay/Year NON NULL, lo status è derivato o separato.
        // Ma impostiamo lo status per completezza, anche se la query del repository controlla i campi direttamente.
        $cancelledIncome->setStatus(Income::STATUS_CANCELLED);
        $this->em->persist($cancelledIncome);

        $this->em->flush();

        // 4. Test del metodo del repository
        $results = $this->repository->findAllNotCanceledForUser($user);

        self::assertCount(1, $results);
        self::assertSame('Active Job', $results[0]->getTitle());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}
