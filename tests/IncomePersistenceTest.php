<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Income;
use App\Entity\IncomeCategory;
use App\Entity\IncomeCharterDetails;
use App\Entity\IncomeContractDetails;
use App\Entity\IncomeFreightDetails;
use App\Entity\IncomeInsuranceDetails;
use App\Entity\IncomeInterestDetails;
use App\Entity\IncomeMailDetails;
use App\Entity\IncomePassengersDetails;
use App\Entity\IncomePrizeDetails;
use App\Entity\IncomeSalvageDetails;
use App\Entity\IncomeServicesDetails;
use App\Entity\IncomeSubsidyDetails;
use App\Entity\IncomeTradeDetails;
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
        $asset = $this->makeAsset($user, 'ISS Contract Runner', 'Free Trader', 'A-2', '1250000.00');
        $category = $this->makeCategory('CONTRACT', 'Contract Work');

        $income = $this->makeIncome(
            $user,
            $asset,
            $category,
            'Survey and Recon Assignment',
            '15000.00',
            112,
            1105
        );

        $details = (new IncomeContractDetails())
            ->setIncome($income)
            ->setJobType('Reconnaissance')
            ->setLocation('Spinward Marches')
            ->setObjective('Map approach vectors')
            ->setSuccessCondition('Deliver nav charts')
            ->setStartDay(112)
            ->setStartYear(1105)
            ->setDeadlineDay(140)
            ->setDeadlineYear(1105)
            ->setBonus('2500.00')
            ->setExpensesPolicy('Fuel and port fees reimbursed')
            ->setDeposit('5000.00')
            ->setRestrictions('No contact with Zhodani assets')
            ->setConfidentialityLevel('Classified Delta')
            ->setFailureTerms('No bonus on failure')
            ->setCancellationTerms('14-day notice')
            ->setPaymentTerms('Net on delivery');

        $income->setContractDetails($details);

        $this->em->persist($user);
        $this->em->persist($asset);
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
        $user = $this->makeUser('freight@log.test');
        $asset = $this->makeAsset($user, 'ISS Freight Runner', 'Far Trader', 'A-1', '2000000.00');
        $category = $this->makeCategory('FREIGHT', 'Freight Haul');

        $income = $this->makeIncome(
            $user,
            $asset,
            $category,
            'Ardan Freight Lot',
            '22000.00',
            115,
            1105
        );

        $details = (new IncomeFreightDetails())
            ->setIncome($income)
            ->setOrigin('Ardan')
            ->setDestination('Rhylanor')
            ->setPickupDay(116)
            ->setPickupYear(1105)
            ->setDeliveryDay(124)
            ->setDeliveryYear(1105)
            ->setDeliveryProofRef('FRT-DEL-884')
            ->setDeliveryProofDay(125)
            ->setDeliveryProofYear(1105)
            ->setDeliveryProofReceivedBy('Rhylanor Cargo Authority')
            ->setCargoDescription('Refined ore pallets')
            ->setCargoQty('40 dtons')
            ->setDeclaredValue('180000.00')
            ->setPaymentTerms('Half upfront, half on delivery')
            ->setLiabilityLimit('75000.00')
            ->setCancellationTerms('Cancel before pickup, 10% fee');

        $income->setFreightDetails($details);

        $this->em->persist($user);
        $this->em->persist($asset);
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

    public function testPersistCharterIncomeWithDetails(): void
    {
        $user = $this->makeUser('charter@log.test');
        $asset = $this->makeAsset($user, 'ISS Charter Vagrant', 'Liner', 'B-1', '3500000.00');
        $category = $this->makeCategory('CHARTER', 'Charter');

        $income = $this->makeIncome(
            $user,
            $asset,
            $category,
            'Aramis Loop Charter',
            '82000.00',
            120,
            1105
        );

        $details = (new IncomeCharterDetails())
            ->setIncome($income)
            ->setAreaOrRoute('Aramis Loop')
            ->setPurpose('Survey charter')
            ->setManifestSummary('Lab gear and sensor rigs')
            ->setStartDay(120)
            ->setStartYear(1105)
            ->setEndDay(160)
            ->setEndYear(1105)
            ->setDeliveryProofRef('CHR-DEL-71')
            ->setDeliveryProofDay(161)
            ->setDeliveryProofYear(1105)
            ->setDeliveryProofReceivedBy('Port Authority')
            ->setPaymentTerms('Monthly retainer')
            ->setDeposit('5000.00')
            ->setExtras('Fuel surcharge applies')
            ->setDamageTerms('Repair at cost')
            ->setCancellationTerms('30-day notice');

        $income->setCharterDetails($details);

        $this->em->persist($user);
        $this->em->persist($asset);
        $this->em->persist($category);
        $this->em->persist($income);
        $this->em->persist($details);
        $this->em->flush();
        $incomeId = $income->getId();
        $this->em->clear();

        $saved = $this->em->find(Income::class, $incomeId);
        self::assertNotNull($saved);
        self::assertSame('CHARTER', $saved->getIncomeCategory()?->getCode());
        self::assertSame('Aramis Loop', $saved->getCharterDetails()?->getAreaOrRoute());
    }

    public function testPersistPassengersIncomeWithDetails(): void
    {
        $user = $this->makeUser('passengers@log.test');
        $asset = $this->makeAsset($user, 'ISS Passenger Dawn', 'Liner', 'C-1', '4200000.00');
        $category = $this->makeCategory('PASSENGERS', 'Passenger Passage');

        $income = $this->makeIncome(
            $user,
            $asset,
            $category,
            'Efate Passenger Run',
            '34000.00',
            200,
            1105
        );

        $details = (new IncomePassengersDetails())
            ->setIncome($income)
            ->setOrigin('Regina')
            ->setDestination('Efate')
            ->setDepartureDay(200)
            ->setDepartureYear(1105)
            ->setArrivalDay(204)
            ->setArrivalYear(1105)
            ->setDeliveryProofRef('PAX-DEL-19')
            ->setDeliveryProofDay(204)
            ->setDeliveryProofYear(1105)
            ->setDeliveryProofReceivedBy('Efate Terminal')
            ->setClassOrBerth('High Passage')
            ->setQty(6)
            ->setPassengerNames('Sonny Jackson, Riva Nal')
            ->setPassengerContact('handler@consortium.test')
            ->setBaggageAllowance('2 trunks per passenger')
            ->setExtraBaggage('Excess billed at port')
            ->setPaymentTerms('Paid on boarding')
            ->setRefundChangePolicy('No refunds after departure');

        $income->setPassengersDetails($details);

        $this->em->persist($user);
        $this->em->persist($asset);
        $this->em->persist($category);
        $this->em->persist($income);
        $this->em->persist($details);
        $this->em->flush();
        $incomeId = $income->getId();
        $this->em->clear();

        $saved = $this->em->find(Income::class, $incomeId);
        self::assertNotNull($saved);
        self::assertSame('PASSENGERS', $saved->getIncomeCategory()?->getCode());
        self::assertSame('High Passage', $saved->getPassengersDetails()?->getClassOrBerth());
    }

    public function testPersistMailIncomeWithDetails(): void
    {
        $user = $this->makeUser('mail@log.test');
        $asset = $this->makeAsset($user, 'ISS Courier Relay', 'Courier', 'B-2', '1800000.00');
        $category = $this->makeCategory('MAIL', 'Mail Dispatch');

        $income = $this->makeIncome(
            $user,
            $asset,
            $category,
            'Imperial Mail Bag',
            '56000.00',
            210,
            1105
        );

        $details = (new IncomeMailDetails())
            ->setIncome($income)
            ->setOrigin('Regina')
            ->setDestination('Mora')
            ->setDispatchDay(210)
            ->setDispatchYear(1105)
            ->setDeliveryDay(217)
            ->setDeliveryYear(1105)
            ->setDeliveryProofRef('MAIL-DEL-447')
            ->setDeliveryProofDay(217)
            ->setDeliveryProofYear(1105)
            ->setDeliveryProofReceivedBy('Mora Postmaster')
            ->setMailType('Imperial Priority')
            ->setPackageCount(12)
            ->setTotalMass('350.50')
            ->setSecurityLevel('Red')
            ->setSealCodes('A1-REDFOX')
            ->setPaymentTerms('Paid on acceptance')
            ->setProofOfDelivery('Stamped by port authority')
            ->setLiabilityLimit('50000.00');

        $income->setMailDetails($details);

        $this->em->persist($user);
        $this->em->persist($asset);
        $this->em->persist($category);
        $this->em->persist($income);
        $this->em->persist($details);
        $this->em->flush();
        $incomeId = $income->getId();
        $this->em->clear();

        $saved = $this->em->find(Income::class, $incomeId);
        self::assertNotNull($saved);
        self::assertSame('MAIL', $saved->getIncomeCategory()?->getCode());
        self::assertSame('Imperial Priority', $saved->getMailDetails()?->getMailType());
    }

    public function testPersistServicesIncomeWithDetails(): void
    {
        $user = $this->makeUser('services@log.test');
        $asset = $this->makeAsset($user, 'ISS Yardrunner', 'Tender', 'D-1', '900000.00');
        $category = $this->makeCategory('SERVICES', 'Services');

        $income = $this->makeIncome(
            $user,
            $asset,
            $category,
            'Regina Refit Work',
            '48000.00',
            90,
            1105
        );

        $details = (new IncomeServicesDetails())
            ->setIncome($income)
            ->setLocation('Regina Highport')
            ->setServiceType('Refit')
            ->setRequestedBy('Port Authority')
            ->setStartDay(90)
            ->setStartYear(1105)
            ->setEndDay(98)
            ->setEndYear(1105)
            ->setDeliveryProofRef('SRV-DEL-33')
            ->setDeliveryProofDay(98)
            ->setDeliveryProofYear(1105)
            ->setDeliveryProofReceivedBy('Yard Chief')
            ->setWorkSummary('Swap reactor seals')
            ->setPartsMaterials('Seal kit Mk-II')
            ->setRisks('High radiation zone')
            ->setPaymentTerms('Net 15')
            ->setExtras('Hazard surcharge')
            ->setLiabilityLimit('120000.00')
            ->setCancellationTerms('Cancel 7 days prior');

        $income->setServicesDetails($details);

        $this->em->persist($user);
        $this->em->persist($asset);
        $this->em->persist($category);
        $this->em->persist($income);
        $this->em->persist($details);
        $this->em->flush();
        $incomeId = $income->getId();
        $this->em->clear();

        $saved = $this->em->find(Income::class, $incomeId);
        self::assertNotNull($saved);
        self::assertSame('SERVICES', $saved->getIncomeCategory()?->getCode());
        self::assertSame('Refit', $saved->getServicesDetails()?->getServiceType());
    }

    public function testPersistSubsidyIncomeWithDetails(): void
    {
        $user = $this->makeUser('subsidy@log.test');
        $asset = $this->makeAsset($user, 'ISS Subsidy Spur', 'Far Trader', 'A-2', '2300000.00');
        $category = $this->makeCategory('SUBSIDY', 'Subsidy');

        $income = $this->makeIncome(
            $user,
            $asset,
            $category,
            'Aramis Subsidy Route',
            '65000.00',
            15,
            1105
        );

        $details = (new IncomeSubsidyDetails())
            ->setIncome($income)
            ->setProgramRef('SUB-AR-9')
            ->setOrigin('Aramis')
            ->setDestination('Regina')
            ->setStartDay(15)
            ->setStartYear(1105)
            ->setEndDay(120)
            ->setEndYear(1105)
            ->setDeliveryProofRef('SUB-DEL-09')
            ->setDeliveryProofDay(121)
            ->setDeliveryProofYear(1105)
            ->setDeliveryProofReceivedBy('Regina Port Office')
            ->setServiceLevel('Priority')
            ->setSubsidyAmount('65000.00')
            ->setPaymentTerms('Quarterly disbursement')
            ->setMilestones('Monthly route report')
            ->setReportingRequirements('Submit manifests')
            ->setNonComplianceTerms('Withhold payment')
            ->setProofRequirements('Signed delivery receipts')
            ->setCancellationTerms('Termination with 30 days notice');

        $income->setSubsidyDetails($details);

        $this->em->persist($user);
        $this->em->persist($asset);
        $this->em->persist($category);
        $this->em->persist($income);
        $this->em->persist($details);
        $this->em->flush();
        $incomeId = $income->getId();
        $this->em->clear();

        $saved = $this->em->find(Income::class, $incomeId);
        self::assertNotNull($saved);
        self::assertSame('SUBSIDY', $saved->getIncomeCategory()?->getCode());
        self::assertSame('SUB-AR-9', $saved->getSubsidyDetails()?->getProgramRef());
    }

    public function testPersistTradeIncomeWithDetails(): void
    {
        $user = $this->makeUser('trade@log.test');
        $asset = $this->makeAsset($user, 'ISS Trade Wind', 'Merchant', 'B-3', '5000000.00');
        $category = $this->makeCategory('TRADE', 'Trade');

        $income = $this->makeIncome(
            $user,
            $asset,
            $category,
            'Mora Catalyst Transfer',
            '90000.00',
            230,
            1105
        );

        $details = (new IncomeTradeDetails())
            ->setIncome($income)
            ->setLocation('Mora')
            ->setTransferPoint('Dock 14')
            ->setTransferCondition('FOB')
            ->setGoodsDescription('Industrial catalysts')
            ->setQty(20)
            ->setGrade('A')
            ->setBatchIds('CAT-44, CAT-45')
            ->setUnitPrice('4500.00')
            ->setPaymentTerms('90000.00')
            ->setDeliveryMethod('Crated transfer')
            ->setDeliveryDay(230)
            ->setDeliveryYear(1105)
            ->setDeliveryProofRef('TRD-DEL-55')
            ->setDeliveryProofDay(231)
            ->setDeliveryProofYear(1105)
            ->setDeliveryProofReceivedBy('Mora Customs')
            ->setAsIsOrWarranty('Warranty')
            ->setWarrantyText('30-day replacement')
            ->setClaimWindow('10 days')
            ->setReturnPolicy('Returns accepted with fee');

        $income->setTradeDetails($details);

        $this->em->persist($user);
        $this->em->persist($asset);
        $this->em->persist($category);
        $this->em->persist($income);
        $this->em->persist($details);
        $this->em->flush();
        $incomeId = $income->getId();
        $this->em->clear();

        $saved = $this->em->find(Income::class, $incomeId);
        self::assertNotNull($saved);
        self::assertSame('TRADE', $saved->getIncomeCategory()?->getCode());
        self::assertSame('Dock 14', $saved->getTradeDetails()?->getTransferPoint());
    }

    public function testPersistSalvageIncomeWithDetails(): void
    {
        $user = $this->makeUser('salvage@log.test');
        $asset = $this->makeAsset($user, 'ISS Salvager', 'Seeker', 'C-3', '2600000.00');
        $category = $this->makeCategory('SALVAGE', 'Salvage');

        $income = $this->makeIncome(
            $user,
            $asset,
            $category,
            'Aramis Belt Salvage',
            '420000.00',
            75,
            1105
        );

        $details = (new IncomeSalvageDetails())
            ->setIncome($income)
            ->setCaseRef('SAL-77')
            ->setSource('Distress Beacon')
            ->setSiteLocation('Aramis Belt')
            ->setRecoveredItemsSummary('Engine cores')
            ->setQtyValue('420000.00')
            ->setHazards('Debris field')
            ->setPaymentTerms('Award after adjudication')
            ->setSplitTerms('Crew 60 / Authority 40')
            ->setRightsBasis('Imperial Salvage Code 17')
            ->setAwardTrigger('Board ruling')
            ->setDisputeProcess('Appeal to sector court');

        $income->setSalvageDetails($details);

        $this->em->persist($user);
        $this->em->persist($asset);
        $this->em->persist($category);
        $this->em->persist($income);
        $this->em->persist($details);
        $this->em->flush();
        $incomeId = $income->getId();
        $this->em->clear();

        $saved = $this->em->find(Income::class, $incomeId);
        self::assertNotNull($saved);
        self::assertSame('SALVAGE', $saved->getIncomeCategory()?->getCode());
        self::assertSame('SAL-77', $saved->getSalvageDetails()?->getCaseRef());
    }

    public function testPersistPrizeIncomeWithDetails(): void
    {
        $user = $this->makeUser('prize@log.test');
        $asset = $this->makeAsset($user, 'ISS Prize Runner', 'Corsair', 'B-5', '7600000.00');
        $category = $this->makeCategory('PRIZE', 'Prize');

        $income = $this->makeIncome(
            $user,
            $asset,
            $category,
            'Confiscated Arms Shipment',
            '950000.00',
            140,
            1105
        );

        $details = (new IncomePrizeDetails())
            ->setIncome($income)
            ->setLegalBasis('Prize Court Order 15')
            ->setCaseRef('PRZ-88')
            ->setPrizeDescription('Confiscated arms shipment')
            ->setEstimatedValue('950000.00')
            ->setDisposition('Auction')
            ->setPaymentTerms('Paid after auction')
            ->setShareSplit('Crew 70 / Crown 30')
            ->setAwardTrigger('Adjudication complete');

        $income->setPrizeDetails($details);

        $this->em->persist($user);
        $this->em->persist($asset);
        $this->em->persist($category);
        $this->em->persist($income);
        $this->em->persist($details);
        $this->em->flush();
        $incomeId = $income->getId();
        $this->em->clear();

        $saved = $this->em->find(Income::class, $incomeId);
        self::assertNotNull($saved);
        self::assertSame('PRIZE', $saved->getIncomeCategory()?->getCode());
        self::assertSame('PRZ-88', $saved->getPrizeDetails()?->getCaseRef());
    }

    public function testPersistInterestIncomeWithDetails(): void
    {
        $user = $this->makeUser('interest@log.test');
        $asset = $this->makeAsset($user, 'ISS Ledger', 'Trader', 'A-3', '3100000.00');
        $category = $this->makeCategory('INTEREST', 'Interest');

        $income = $this->makeIncome(
            $user,
            $asset,
            $category,
            'Imperial Bond Interest',
            '5000.00',
            365,
            1105
        );

        $details = (new IncomeInterestDetails())
            ->setIncome($income)
            ->setAccountRef('INT-444')
            ->setInstrument('Imperial Bond')
            ->setPrincipal('150000.00')
            ->setInterestRate('3.50')
            ->setStartDay(1)
            ->setStartYear(1105)
            ->setEndDay(365)
            ->setEndYear(1105)
            ->setCalcMethod('Compound monthly')
            ->setInterestEarned('5250.00')
            ->setNetPaid('5000.00')
            ->setPaymentTerms('Paid at term')
            ->setDisputeWindow('30 days');

        $income->setInterestDetails($details);

        $this->em->persist($user);
        $this->em->persist($asset);
        $this->em->persist($category);
        $this->em->persist($income);
        $this->em->persist($details);
        $this->em->flush();
        $incomeId = $income->getId();
        $this->em->clear();

        $saved = $this->em->find(Income::class, $incomeId);
        self::assertNotNull($saved);
        self::assertSame('INTEREST', $saved->getIncomeCategory()?->getCode());
        self::assertSame('Imperial Bond', $saved->getInterestDetails()?->getInstrument());
    }

    public function testPersistInsuranceIncomeWithDetails(): void
    {
        $user = $this->makeUser('insurance@log.test');
        $asset = $this->makeAsset($user, 'ISS Claims', 'Trader', 'B-2', '2750000.00');
        $category = $this->makeCategory('INSURANCE', 'Insurance');

        $income = $this->makeIncome(
            $user,
            $asset,
            $category,
            'Hull Loss Settlement',
            '250000.00',
            88,
            1105
        );

        $details = (new IncomeInsuranceDetails())
            ->setIncome($income)
            ->setIncidentRef('INC-12')
            ->setIncidentDay(88)
            ->setIncidentYear(1105)
            ->setIncidentLocation('Regina orbit')
            ->setIncidentCause('Micrometeor strike')
            ->setLossType('Hull breach')
            ->setVerifiedLoss('250000.00')
            ->setDeductible('5000.00')
            ->setPaymentTerms('Settlement on acceptance')
            ->setAcceptanceEffect('Acceptance waives further claims')
            ->setSubrogationTerms('Rights transfer to insurer')
            ->setCoverageNotes('Excludes hostile action');

        $income->setInsuranceDetails($details);

        $this->em->persist($user);
        $this->em->persist($asset);
        $this->em->persist($category);
        $this->em->persist($income);
        $this->em->persist($details);
        $this->em->flush();
        $incomeId = $income->getId();
        $this->em->clear();

        $saved = $this->em->find(Income::class, $incomeId);
        self::assertNotNull($saved);
        self::assertSame('INSURANCE', $saved->getIncomeCategory()?->getCode());
        self::assertSame('INC-12', $saved->getInsuranceDetails()?->getIncidentRef());
    }

    private function makeUser(string $email): User
    {
        return (new User())
            ->setEmail($email)
            ->setPassword('hash');
    }

    private function makeAsset(User $user, string $name, string $type, string $class, string $price): Asset
    {
        return (new Asset())
            ->setName($name)
            ->setType($type)
            ->setClass($class)
            ->setPrice($price)
            ->setUser($user);
    }

    private function makeCategory(string $code, string $description): IncomeCategory
    {
        return (new IncomeCategory())
            ->setCode($code)
            ->setDescription($description);
    }

    private function makeIncome(
        User $user,
        Asset $asset,
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
            ->setAsset($asset)
            ->setUser($user);
    }
}
