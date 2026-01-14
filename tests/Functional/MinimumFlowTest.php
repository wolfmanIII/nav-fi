<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Cost;
use App\Entity\CostCategory;
use App\Entity\Crew;
use App\Entity\Income;
use App\Entity\IncomeCategory;
use App\Entity\Ship;
use App\Entity\User;
use App\Service\PdfGenerator;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MinimumFlowTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();

        $dbPath = dirname(__DIR__, 2) . '/var/test.db';
        if (is_file($dbPath)) {
            unlink($dbPath);
        }

        $_SERVER['DATABASE_URL'] = 'sqlite:///' . $dbPath;
        $_ENV['DATABASE_URL'] = $_SERVER['DATABASE_URL'];

        $this->client = static::createClient();
        $this->em = static::getContainer()->get('doctrine')->getManager();

        $connection = $this->em->getConnection();
        if (!Type::hasType('uuid')) {
            Type::addType('uuid', StringType::class);
        }
        $connection->getDatabasePlatform()->registerDoctrineTypeMapping('uuid', 'string');

        $schemaTool = new SchemaTool($this->em);
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        static::getContainer()->set(PdfGenerator::class, new class extends PdfGenerator {
            public function __construct() {}

            public function render(string $template, array $context = [], array $options = []): string
            {
                return '%PDF-FAKE%';
            }

            public function renderFromHtml(string $html, array $options = []): string
            {
                return '%PDF-FAKE%';
            }
        });
    }

    public function testShipIndexFiltersAndPagination(): void
    {
        $user = $this->createUser('ship@test.local');

        for ($i = 1; $i <= 12; $i++) {
            $this->em->persist($this->createShip($user, sprintf('ISS Ship %02d', $i)));
        }

        $this->em->flush();
        $this->login($user);

        $crawler = $this->client->request('GET', '/ship/index?page=2');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('LOG_SECTOR: 11-12', $crawler->filter('#pagination-metrics')->text());
        self::assertStringContainsString('TOTAL_RECORDS: 12', $crawler->filter('#pagination-metrics')->text());

        $this->client->request('GET', '/ship/index?name=ISS Ship 03');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('ISS Ship 03', $this->client->getResponse()->getContent());
    }

    public function testCostIndexFiltersAndPagination(): void
    {
        $user = $this->createUser('cost@test.local');
        $ship = $this->createShip($user, 'ISS Cost Runner');
        $category = $this->createCostCategory('SHIP_GEAR', 'Ship Gear');

        $this->em->persist($ship);
        $this->em->persist($category);

        for ($i = 1; $i <= 12; $i++) {
            $cost = (new Cost())
                ->setUser($user)
                ->setShip($ship)
                ->setCostCategory($category)
                ->setTitle(sprintf('Cost Entry %02d', $i))
                ->setAmount('1000.00');
            $this->em->persist($cost);
        }

        $this->em->flush();
        $this->login($user);

        $crawler = $this->client->request('GET', '/cost/index?page=2');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('LOG_SECTOR: 11-12', $crawler->filter('#pagination-metrics')->text());
        self::assertStringContainsString('TOTAL_RECORDS: 12', $crawler->filter('#pagination-metrics')->text());

        $this->client->request('GET', '/cost/index?title=Cost Entry 05');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Cost Entry 05', $this->client->getResponse()->getContent());
    }

    public function testIncomeIndexFiltersAndPagination(): void
    {
        $user = $this->createUser('income@test.local');
        $ship = $this->createShip($user, 'ISS Income Runner');
        $category = $this->createIncomeCategory('CONTRACT', 'Contract');

        $this->em->persist($ship);
        $this->em->persist($category);

        for ($i = 1; $i <= 12; $i++) {
            $income = (new Income())
                ->setUser($user)
                ->setShip($ship)
                ->setIncomeCategory($category)
                ->setTitle(sprintf('Income Entry %02d', $i))
                ->setAmount('5000.00');
            $this->em->persist($income);
        }

        $this->em->flush();
        $this->login($user);

        $crawler = $this->client->request('GET', '/income/index?page=2');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('LOG_SECTOR: 11-12', $crawler->filter('#pagination-metrics')->text());
        self::assertStringContainsString('TOTAL_RECORDS: 12', $crawler->filter('#pagination-metrics')->text());

        $this->client->request('GET', '/income/index?title=Income Entry 04');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Income Entry 04', $this->client->getResponse()->getContent());
    }

    public function testCrewIndexFiltersAndPagination(): void
    {
        $user = $this->createUser('crew@test.local');

        for ($i = 1; $i <= 12; $i++) {
            $crew = (new Crew())
                ->setUser($user)
                ->setName(sprintf('Crew %02d', $i))
                ->setSurname('Tester');
            $this->em->persist($crew);
        }

        $this->em->flush();
        $this->login($user);

        $crawler = $this->client->request('GET', '/crew/index?page=2');
        self::assertResponseIsSuccessful();
        // Crew perPage is 9, so page 2 of 12 is records 10-12
        self::assertStringContainsString('LOG_SECTOR: 10-12', $crawler->filter('#pagination-metrics')->text());
        self::assertStringContainsString('TOTAL_RECORDS: 12', $crawler->filter('#pagination-metrics')->text());

        $this->client->request('GET', '/crew/index?search=Crew 07');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Crew 07', $this->client->getResponse()->getContent());
    }

    public function testOwnershipReturns404ForForeignShip(): void
    {
        $owner = $this->createUser('owner@test.local');
        $other = $this->createUser('other@test.local');
        $ship = $this->createShip($owner, 'ISS Private');

        $this->em->persist($ship);
        $this->em->flush();

        $this->login($other);
        $this->client->request('GET', '/ship/edit/' . $ship->getId());
        self::assertResponseStatusCodeSame(404);
    }

    public function testOwnershipReturns404ForForeignIncome(): void
    {
        $owner = $this->createUser('income-owner@test.local');
        $other = $this->createUser('income-other@test.local');
        $ship = $this->createShip($owner, 'ISS Contract');
        $category = $this->createIncomeCategory('CONTRACT', 'Contract');

        $this->em->persist($ship);
        $this->em->persist($category);

        $income = (new Income())
            ->setUser($owner)
            ->setShip($ship)
            ->setIncomeCategory($category)
            ->setTitle('Private Contract')
            ->setAmount('9000.00');
        $this->em->persist($income);
        $this->em->flush();

        $this->login($other);
        $this->client->request('GET', '/income/edit/' . $income->getId());
        self::assertResponseStatusCodeSame(404);
    }

    public function testPdfEndpointsReturnPdf(): void
    {
        $user = $this->createUser('pdf@test.local');
        $ship = $this->createShip($user, 'ISS PDF');
        $costCategory = $this->createCostCategory('SHIP_GEAR', 'Ship Gear');
        $incomeCategory = $this->createIncomeCategory('CONTRACT', 'Contract');

        $this->em->persist($ship);
        $this->em->persist($costCategory);
        $this->em->persist($incomeCategory);

        $cost = (new Cost())
            ->setUser($user)
            ->setShip($ship)
            ->setCostCategory($costCategory)
            ->setTitle('Hull Repairs')
            ->setAmount('1200.00');
        $this->em->persist($cost);

        $income = (new Income())
            ->setUser($user)
            ->setShip($ship)
            ->setIncomeCategory($incomeCategory)
            ->setTitle('Contract')
            ->setAmount('8000.00');
        $this->em->persist($income);

        $this->em->flush();
        $this->login($user);

        $this->client->request('GET', '/cost/' . $cost->getId() . '/pdf');
        self::assertResponseIsSuccessful();
        self::assertSame('application/pdf', $this->client->getResponse()->headers->get('Content-Type'));

        $this->client->request('GET', '/income/' . $income->getId() . '/pdf');
        self::assertResponseIsSuccessful();
        self::assertSame('application/pdf', $this->client->getResponse()->headers->get('Content-Type'));
    }

    private function login(User $user): void
    {
        $this->client->loginUser($user);
    }

    private function createUser(string $email): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setRoles(['ROLE_USER'])
            ->setPassword('hash');

        $this->em->persist($user);

        return $user;
    }

    private function createShip(User $user, string $name): Ship
    {
        return (new Ship())
            ->setUser($user)
            ->setName($name)
            ->setType('Trader')
            ->setClass('A-1')
            ->setPrice('2500000.00');
    }

    private function createCostCategory(string $code, string $description): CostCategory
    {
        return (new CostCategory())
            ->setCode($code)
            ->setDescription($description);
    }

    private function createIncomeCategory(string $code, string $description): IncomeCategory
    {
        return (new IncomeCategory())
            ->setCode($code)
            ->setDescription($description);
    }
}
