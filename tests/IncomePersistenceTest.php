<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\FinancialAccount;
use App\Entity\Income;
use App\Entity\IncomeCategory;

use App\Entity\Asset;
use App\Entity\User;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class IncomePersistenceTest extends TestCase
{
    private ?EntityManagerInterface $em = null;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [dirname(__DIR__) . '/src/Entity'],
            isDevMode: true,
        );
        $config->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER, true));

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $config);

        if (!Type::hasType('uuid')) {
            Type::addType('uuid', \Doctrine\DBAL\Types\StringType::class);
        }
        if (!Type::hasType('uuid_binary')) {
            Type::addType('uuid_binary', \Doctrine\DBAL\Types\StringType::class);
        }

        $platform = $connection->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('uuid', 'string');
        $platform->registerDoctrineTypeMapping('uuid_binary', 'string');

        $this->em = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        if ($this->em !== null) {
            $this->em->close();
        }
        $this->em = null;
        parent::tearDown();
    }

    public function testPersistContractIncomeWithDetails(): void
    {
        $user = $this->makeUser('captain@log.test');
        $fa = $this->createFinancialAccount($user, 'ISS Contract Runner');
        $category = $this->makeCategory('CONTRACT', 'Contract Work');
        $category = $this->makeCategory('CONTRACT', 'Contract Work');

        $income = $this->makeIncome(
            $user,
            $fa,
            $category,
            'Survey and Recon Assignment',
            '15000.00',
            112,
            1105
        );

        $details = [
            'jobType' => 'Reconnaissance',
            'location' => 'Spinward Marches',
            'objective' => 'Map approach vectors',
            'successCondition' => 'Deliver nav charts',
            'startDay' => 112,
            'startYear' => 1105,
            'deadlineDay' => 140,
            'deadlineYear' => 1105,
            'bonus' => '2500.00',
            'expensesPolicy' => 'Fuel and port fees reimbursed',
            'deposit' => '5000.00',
            'restrictions' => 'No contact with Zhodani assets',
            'confidentialityLevel' => 'Classified Delta',
            'failureTerms' => 'No bonus on failure',
            'cancellationTerms' => '14-day notice',
            'paymentTerms' => 'Net on delivery'
        ];

        $income->setDetails($details);

        $this->em->persist($user);
        $this->em->persist($fa);
        $this->em->persist($category);
        $this->em->persist($income);
        $this->em->flush();
        $incomeId = $income->getId();
        $this->em->clear();

        $saved = $this->em->find(Income::class, $incomeId);
        self::assertNotNull($saved);
        self::assertSame('CONTRACT', $saved->getIncomeCategory()?->getCode());
        $savedDetails = $saved->getDetails();
        self::assertSame('Spinward Marches', $savedDetails['location']);
        self::assertSame('Reconnaissance', $savedDetails['jobType']);
    }

    public function testPersistFreightIncomeWithDetails(): void
    {
        $user = $this->makeUser('freight@log.test');
        $fa = $this->createFinancialAccount($user, 'ISS Freight Runner');
        $category = $this->makeCategory('FREIGHT', 'Freight Haul');
        $category = $this->makeCategory('FREIGHT', 'Freight Haul');

        $income = $this->makeIncome(
            $user,
            $fa,
            $category,
            'Ardan Freight Lot',
            '22000.00',
            115,
            1105
        );

        $details = [
            'origin' => 'Ardan',
            'destination' => 'Rhylanor',
            'pickupDay' => 116,
            'pickupYear' => 1105,
            'deliveryDay' => 124,
            'deliveryYear' => 1105,
            'deliveryProofRef' => 'FRT-DEL-884',
            'deliveryProofDay' => 125,
            'deliveryProofYear' => 1105,
            'deliveryProofReceivedBy' => 'Rhylanor Cargo Authority',
            'cargoDescription' => 'Refined ore pallets',
            'cargoQty' => '40 dtons',
            'declaredValue' => '180000.00',
            'paymentTerms' => 'Half upfront, half on delivery',
            'liabilityLimit' => '75000.00',
            'cancellationTerms' => 'Cancel before pickup, 10% fee'
        ];

        $income->setDetails($details);

        $this->em->persist($user);
        $this->em->persist($fa);
        $this->em->persist($category);
        $this->em->persist($income);
        $this->em->flush();
        $incomeId = $income->getId();
        $this->em->clear();

        $saved = $this->em->find(Income::class, $incomeId);
        self::assertNotNull($saved);
        self::assertSame('FREIGHT', $saved->getIncomeCategory()?->getCode());
        $savedDetails = $saved->getDetails();
        self::assertSame('Rhylanor', $savedDetails['destination']);
        self::assertSame('40 dtons', $savedDetails['cargoQty']);
    }

    public function testPersistTradeIncomeWithDetails(): void
    {
        $user = $this->makeUser('trade@log.test');
        $fa = $this->createFinancialAccount($user, 'ISS Trade Wind');
        $category = $this->makeCategory('TRADE', 'Trade');
        $category = $this->makeCategory('TRADE', 'Trade');

        $income = $this->makeIncome(
            $user,
            $fa,
            $category,
            'Mora Catalyst Transfer',
            '90000.00',
            230,
            1105
        );

        $details = [
            'location' => 'Mora',
            'transferPoint' => 'Dock 14',
            'transferCondition' => 'FOB',
            'goodsDescription' => 'Industrial catalysts',
            'qty' => 20,
            'grade' => 'A',
            'batchIds' => 'CAT-44, CAT-45',
            'unitPrice' => '4500.00',
            'paymentTerms' => '90000.00',
            'deliveryMethod' => 'Crated transfer',
            'deliveryDay' => 230,
            'deliveryYear' => 1105,
            'deliveryProofRef' => 'TRD-DEL-55',
            'deliveryProofDay' => 231,
            'deliveryProofYear' => 1105,
            'deliveryProofReceivedBy' => 'Mora Customs',
            'asIsOrWarranty' => 'Warranty',
            'warrantyText' => '30-day replacement',
            'claimWindow' => '10 days',
            'returnPolicy' => 'Returns accepted with fee'
        ];

        $income->setDetails($details);

        $this->em->persist($user);
        $this->em->persist($fa);
        $this->em->persist($category);
        $this->em->persist($income);
        $this->em->flush();
        $incomeId = $income->getId();
        $this->em->clear();

        $saved = $this->em->find(Income::class, $incomeId);
        self::assertNotNull($saved);
        self::assertSame('TRADE', $saved->getIncomeCategory()?->getCode());
        $savedDetails = $saved->getDetails();
        self::assertSame('Dock 14', $savedDetails['transferPoint']);
        self::assertSame('Industrial catalysts', $savedDetails['goodsDescription']);
    }

    private function makeUser(string $email): User
    {
        return (new User())
            ->setEmail($email)
            ->setPassword('hash');
    }

    private function createFinancialAccount(User $user, string $assetName): FinancialAccount
    {
        $asset = new Asset();
        $asset->setName($assetName);
        $asset->setType('Trader');
        $asset->setClass('A-1');
        $asset->setPrice('1000000.00');
        $asset->setUser($user);
        $this->em->persist($asset);

        $fa = new FinancialAccount();
        $fa->setAsset($asset);
        $fa->setUser($user);

        return $fa;
    }

    private function makeCategory(string $code, string $description): IncomeCategory
    {
        return (new IncomeCategory())
            ->setCode($code)
            ->setDescription($description);
    }

    private function makeIncome(
        User $user,
        FinancialAccount $fa,
        IncomeCategory $category,
        string $title,
        string $amount,
        int $signingDay,
        int $signingYear
    ): Income {
        return (new Income())
            ->setCode((string) Uuid::v7())
            ->setTitle($title)
            ->setAmount($amount)
            ->setSigningDay($signingDay)
            ->setSigningYear($signingYear)
            ->setIncomeCategory($category)
            ->setFinancialAccount($fa)
            ->setUser($user)
            ->setStatus(Income::STATUS_SIGNED);
    }
}
