<?php

namespace App\Tests\Functional;

use App\Entity\Asset;
use App\Entity\BrokerOpportunity;
use App\Entity\Campaign;
use App\Entity\CostCategory;
use App\Service\Cube\BrokerService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ContractAcceptanceTest extends KernelTestCase
{
    private BrokerService $brokerService;
    private $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->brokerService = static::getContainer()->get(BrokerService::class);
        $this->em = static::getContainer()->get('doctrine')->getManager();

        // Ensure we have a CostCategory
        $repo = $this->em->getRepository(CostCategory::class);
        if (!$repo->findOneBy(['code' => 'ASSET_RESUPPLY'])) {
            $cat = new CostCategory();
            $cat->setCode('ASSET_RESUPPLY');
            $cat->setDescription('Resupply');
            $this->em->persist($cat);
            $this->em->flush();
        }
    }

    public function testAcceptfreightOpportunity()
    {
        // Setup
        $campaign = new Campaign();
        $campaign->setTitle('Test Campaign');
        $this->em->persist($campaign);

        $asset = new Asset();
        $asset->setName('Test Ship');
        $asset->setCampaign($campaign);
        $asset->setType('Starship');
        $this->em->persist($asset);

        $session = $this->brokerService->createSession($campaign, 'Spinward Marches', '1910', 2);

        // Mock Freight Opportunity
        $opp = new BrokerOpportunity();
        $opp->setSession($session);
        $opp->setSummary('Test Freight');
        $opp->setAmount('5000');
        $opp->setStatus('SAVED');
        $opp->setData([
            'type' => 'FREIGHT',
            'amount' => 5000,
            'summary' => 'Test Freight',
            'details' => [
                'origin' => 'Regina',
                'destination' => 'Jenghe',
                'start_day' => 100,
                'start_year' => 1105
            ]
        ]);
        $this->em->persist($opp);
        $this->em->flush();

        // Action
        $income = $this->brokerService->acceptOpportunity($opp, $asset);

        // Verify
        $this->assertEquals('CONVERTED', $opp->getStatus());
        $this->assertInstanceOf(\App\Entity\Income::class, $income);
        $this->assertEquals('5000', $income->getAmount());

        // Verify no cost created for Freight
        $costRepo = $this->em->getRepository(\App\Entity\Cost::class);
        $costs = $costRepo->findBy(['asset' => $asset]);
        $this->assertEmpty($costs, 'Freight should not assume costs');
    }

    public function testAcceptTradeOpportunityCreatesCost()
    {
        // Setup
        $campaign = new Campaign();
        $campaign->setTitle('Test Campaign 2');
        $this->em->persist($campaign);

        $asset = new Asset();
        $asset->setName('Merchant Ship');
        $asset->setCampaign($campaign);
        $asset->setType('Starship');
        $this->em->persist($asset);

        $session = $this->brokerService->createSession($campaign, 'Spinward Marches', '1910', 2);

        // Mock Trade Opportunity
        $opp = new BrokerOpportunity();
        $opp->setSession($session);
        $opp->setSummary('Trade Deal');
        $opp->setAmount('10000'); // This is the COST to buy
        $opp->setStatus('SAVED');
        $opp->setData([
            'type' => 'TRADE',
            'amount' => 10000,
            'summary' => 'Trade Deal',
            'details' => [
                'goods' => 'Electronics',
                'tons' => 10,
                'origin' => 'Regina',
                'destination' => 'Jenghe',
                'start_day' => 110,
                'start_year' => 1105
            ]
        ]);
        $this->em->persist($opp);
        $this->em->flush();

        // Action
        $result = $this->brokerService->acceptOpportunity($opp, $asset);

        // Verify
        $this->assertEquals('CONVERTED', $opp->getStatus());

        // Result should be Cost
        $this->assertInstanceOf(\App\Entity\Cost::class, $result);
        $this->assertEquals('10000', $result->getAmount());
        $this->assertStringContainsString('Trade Purchase', $result->getTitle()); // as set by converter

        // Check Cost persistence
        $costRepo = $this->em->getRepository(\App\Entity\Cost::class);
        $costs = $costRepo->findBy(['asset' => $asset]);
        $this->assertCount(1, $costs, 'Trade acceptance must create a Cost entity');
    }
}
