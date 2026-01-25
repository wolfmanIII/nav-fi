<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\Asset;
use App\Entity\Campaign;
use App\Entity\Cost;
use App\Entity\Income;
use App\Entity\IncomeCategory;
use App\Entity\CostCategory;
use App\Service\Cube\BrokerService;
use App\Dto\Cube\CubeOpportunityData;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * NAV-FI COMPREHENSIVE WORKFLOW VERIFICATION
 * 
 * Verifies all mission types and trade cycles.
 */
class ComprehensiveWorkflowTest extends KernelTestCase
{
    private $em;
    private $brokerService;
    private $user;
    private $campaign;
    private $asset;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get('doctrine')->getManager();
        $this->brokerService = static::getContainer()->get(BrokerService::class);

        // Reset Schema
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $this->seedCategories();
        $this->setupBaseEntities();
    }

    private function setupBaseEntities(): void
    {
        $this->user = new User();
        $this->user->setEmail('commander@navfi.com');
        $this->user->setPassword('secure');
        $this->em->persist($this->user);

        $this->campaign = new Campaign();
        $this->campaign->setTitle("The Spinward Run");
        $this->campaign->setUser($this->user);
        $this->campaign->setSessionDay(10);
        $this->campaign->setSessionYear(1105);
        $this->em->persist($this->campaign);

        $this->asset = new Asset();
        $this->asset->setName("Fat Trader");
        $this->asset->setCategory('ship');
        $this->asset->setUser($this->user);
        $this->asset->setCampaign($this->campaign);
        $this->asset->setCredits(500000);
        $this->em->persist($this->asset);

        $this->em->flush();
    }

    private function seedCategories(): void
    {
        $codes = ['TRADE', 'FREIGHT', 'PASSENGERS', 'MAIL', 'CONTRACT'];
        foreach ($codes as $code) {
            $cc = new CostCategory();
            $cc->setCode($code);
            $cc->setDescription($code);
            $this->em->persist($cc);

            $ic = new IncomeCategory();
            $ic->setCode($code);
            $ic->setDescription($code);
            $this->em->persist($ic);
        }
        $this->em->flush();
    }

    public function testFreightConversionWithDateOverride(): void
    {
        $session = $this->brokerService->createSession($this->campaign, 'Spinward', '1905', 2);

        $opp = $this->brokerService->saveOpportunity($session, [
            'type' => 'FREIGHT',
            'summary' => 'Freight to Regina',
            'amount' => 12000,
            'distance' => 1,
            'details' => [
                'origin' => 'Mora',
                'destination' => 'Regina',
                'tons' => 20,
                'cargo_type' => 'Industrial Parts',
                'start_day' => 10,
                'start_year' => 1105
            ]
        ]);

        // ACCEPT with Override
        $income = $this->brokerService->acceptOpportunity($opp, $this->asset, [
            'day' => 15,
            'year' => 1105
        ]);

        $this->assertInstanceOf(Income::class, $income);
        $this->assertEquals(15, $income->getSigningDay());
        $this->assertEquals(12000, $income->getAmount());

        $details = $income->getDetails();
        $this->assertIsArray($details);
        $this->assertEquals(15, $details['pickupDay']);
        $this->assertEquals('Industrial Parts', $details['cargoDescription']);
        $this->assertEquals('Regina', $details['destination']);
        $this->assertEquals('Mora', $details['origin']);
    }

    public function testPassengerConversionWithDateOverride(): void
    {
        $session = $this->brokerService->createSession($this->campaign, 'Spinward', '1905', 2);

        $opp = $this->brokerService->saveOpportunity($session, [
            'type' => 'PASSENGERS',
            'summary' => '6x Middle Passage',
            'amount' => 48000,
            'distance' => 2,
            'details' => [
                'origin' => 'Mora',
                'destination' => 'Efate',
                'pax' => 6,
                'class' => 'middle',
                'start_day' => 10,
                'start_year' => 1105
            ]
        ]);

        // ACCEPT with Override
        $income = $this->brokerService->acceptOpportunity($opp, $this->asset, [
            'day' => 12,
            'year' => 1105
        ]);

        $this->assertInstanceOf(Income::class, $income);
        $this->assertEquals(12, $income->getSigningDay());

        $details = $income->getDetails();
        $this->assertIsArray($details);
        $this->assertEquals(6, $details['qty']);
        $this->assertEquals(12, $details['departureDay']);
    }

    public function testMailConversionWithDateOverride(): void
    {
        $session = $this->brokerService->createSession($this->campaign, 'Spinward', '1905', 2);

        $opp = $this->brokerService->saveOpportunity($session, [
            'type' => 'MAIL',
            'summary' => 'Xboat Mail Pack',
            'amount' => 5000,
            'distance' => 1,
            'details' => [
                'origin' => 'Mora',
                'destination' => 'Regina',
                'containers' => 2,
                'tons' => 10,
                'start_day' => 10,
                'start_year' => 1105
            ]
        ]);

        // ACCEPT with Override
        $income = $this->brokerService->acceptOpportunity($opp, $this->asset, [
            'day' => 11,
            'year' => 1105
        ]);

        $this->assertInstanceOf(Income::class, $income);
        $details = $income->getDetails();
        $this->assertIsArray($details);
        $this->assertEquals(11, $details['dispatchDay']);
        $this->assertEquals('Official Priority', $details['mailType']);
    }

    public function testContractConversionWithDeadline(): void
    {
        $session = $this->brokerService->createSession($this->campaign, 'Spinward', '1905', 2);

        $opp = $this->brokerService->saveOpportunity($session, [
            'type' => 'CONTRACT',
            'summary' => 'Extraction Mission',
            'amount' => 150000,
            'distance' => 0,
            'details' => [
                'origin' => 'Local',
                'mission_type' => 'Extraction',
                'start_day' => 10,
                'start_year' => 1105
            ]
        ]);

        // ACCEPT with Deadline
        $income = $this->brokerService->acceptOpportunity($opp, $this->asset, [
            'day' => 20,
            'year' => 1105,
            'deadline_day' => 30,
            'deadline_year' => 1105
        ]);

        $this->assertInstanceOf(Income::class, $income);
        $details = $income->getDetails();
        $this->assertIsArray($details);
        $this->assertEquals(20, $details['startDay']);
        $this->assertEquals(30, $details['deadlineDay']);
    }

    public function testFullTradeLifecycle(): void
    {
        $session = $this->brokerService->createSession($this->campaign, 'Spinward', '1905', 2);

        $opp = $this->brokerService->saveOpportunity($session, [
            'type' => 'TRADE',
            'summary' => 'Textiles to Lunion',
            'amount' => 30000,
            'distance' => 1,
            'details' => [
                'goods' => 'Textiles',
                'tons' => 20,
                'destination' => 'Lunion',
                'origin' => 'Mora',
                'origin_hex' => '1905',
                'start_day' => 10,
                'start_year' => 1105
            ]
        ]);

        // 1. BUY
        $cost = $this->brokerService->acceptOpportunity($opp, $this->asset);
        $this->assertInstanceOf(Cost::class, $cost);

        // Check Unsold
        $unsold = $this->em->getRepository(Cost::class)->findUnsoldTradeGoods($this->user);
        $this->assertCount(1, $unsold);
        $this->assertEquals($cost->getId(), $unsold[0]->getId());

        // 2. SELL (Simulate TradeController.php logic)
        $income = new Income();
        $income->setUser($this->user);
        $income->setAsset($this->asset);
        $income->setIncomeCategory($this->em->getRepository(IncomeCategory::class)->findOneBy(['code' => 'TRADE']));
        $income->setTitle('Sale: ' . $cost->getTitle());
        $income->setAmount('45000');
        $income->setSigningDay(18);
        $income->setSigningYear(1105);
        $income->setPurchaseCost($cost);

        $income->setDetails([
            'qty' => 20,
            'goodsDescription' => 'Textiles',
            'unitPrice' => '2250'
        ]);

        $this->em->persist($income);

        // Mark as Sold (using regular persist to avoid full manager complexity here)
        $cost->setDetailItems([['description' => 'Textiles', 'quantity' => 20, 'sold' => true]]);

        $this->em->flush();

        // 3. FINAL VERIFICATION
        $unsoldAfter = $this->em->getRepository(Cost::class)->findUnsoldTradeGoods($this->user);
        $this->assertCount(0, $unsoldAfter, "Cargo should be removed from unsold list");

        $this->assertEquals(45000, $income->getAmount());
        $this->assertEquals($cost->getId(), $income->getPurchaseCost()->getId());
    }
}
