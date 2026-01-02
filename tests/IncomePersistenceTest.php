<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Income;
use App\Entity\IncomeCategory;
use App\Entity\IncomeContractDetails;
use App\Entity\IncomeFreightDetails;
use App\Entity\Ship;
use App\Entity\User;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
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

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $config);

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
        $user = (new User())
            ->setEmail('captain@log.test')
            ->setPassword('hash');

        $ship = (new Ship())
            ->setName('ISS Contract Runner')
            ->setType('Free Trader')
            ->setClass('A-2')
            ->setPrice(1_250_000.00)
            ->setUser($user);

        $category = (new IncomeCategory())
            ->setCode('CONTRACT')
            ->setDescription('Contract Work');

        $income = (new Income())
            ->setCode((string) Uuid::v7())
            ->setTitle('Survey and Recon Assignment')
            ->setAmount('15000.00')
            ->setSigningDay(112)
            ->setSigningYear(1105)
            ->setIncomeCategory($category)
            ->setShip($ship)
            ->setUser($user);

        $details = (new IncomeContractDetails())
            ->setIncome($income)
            ->setJobType('Reconnaissance')
            ->setLocation('Spinward Marches')
            ->setObjective('Map approach vectors')
            ->setSuccessCondition('Deliver nav charts')
            ->setPaymentTerms('Net on delivery');

        $income->setContractDetails($details);

        $this->em->persist($user);
        $this->em->persist($ship);
        $this->em->persist($category);
        $this->em->persist($income);
        $this->em->persist($details);
        $this->em->flush();
        $incomeId = $income->getId();
        $this->em->clear();

        $saved = $this->em->find(Income::class, $incomeId);
        self::assertNotNull($saved);
        self::assertSame('CONTRACT', $saved->getIncomeCategory()?->getCode());
        self::assertSame('Spinward Marches', $saved->getContractDetails()?->getLocation());
    }

    public function testPersistFreightIncomeWithDetails(): void
    {
        $user = (new User())
            ->setEmail('freight@log.test')
            ->setPassword('hash');

        $ship = (new Ship())
            ->setName('ISS Freight Runner')
            ->setType('Far Trader')
            ->setClass('A-1')
            ->setPrice(2_000_000.00)
            ->setUser($user);

        $category = (new IncomeCategory())
            ->setCode('FREIGHT')
            ->setDescription('Freight Haul');

        $income = (new Income())
            ->setCode((string) Uuid::v7())
            ->setTitle('Ardan Freight Lot')
            ->setAmount('22000.00')
            ->setSigningDay(115)
            ->setSigningYear(1105)
            ->setIncomeCategory($category)
            ->setShip($ship)
            ->setUser($user);

        $details = (new IncomeFreightDetails())
            ->setIncome($income)
            ->setOrigin('Ardan')
            ->setDestination('Rhylanor')
            ->setPickupDay(116)
            ->setPickupYear(1105)
            ->setDeliveryDay(124)
            ->setDeliveryYear(1105)
            ->setCargoDescription('Refined ore pallets')
            ->setCargoQty('40 dtons')
            ->setDeclaredValue('180000.00')
            ->setPaymentTerms('Half upfront, half on delivery');

        $income->setFreightDetails($details);

        $this->em->persist($user);
        $this->em->persist($ship);
        $this->em->persist($category);
        $this->em->persist($income);
        $this->em->persist($details);
        $this->em->flush();
        $incomeId = $income->getId();
        $this->em->clear();

        $saved = $this->em->find(Income::class, $incomeId);
        self::assertNotNull($saved);
        self::assertSame('FREIGHT', $saved->getIncomeCategory()?->getCode());
        self::assertSame('Rhylanor', $saved->getFreightDetails()?->getDestination());
        self::assertSame('40 dtons', $saved->getFreightDetails()?->getCargoQty());
    }
}
