<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\Asset;
use App\Entity\Campaign;
use App\Entity\Cost;
use App\Entity\Income;
use App\Service\Cube\BrokerService;
use App\Service\Cube\OpportunityConverter;
use App\Dto\Cube\CubeOpportunityData;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FullRouteTest extends KernelTestCase
{
    private $em;
    private $brokerService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get('doctrine')->getManager();
        $this->brokerService = static::getContainer()->get(BrokerService::class);

        // Reset Schema for clean state
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testFullTradeRoute(): void
    {

        // 1. SETUP
        $user = new User();
        $user->setEmail('test_trade_' . uniqid() . '@navfi.com');
        $user->setPassword('password');
        $this->em->persist($user);

        // Need ID before persisting related entities? No, doctrine handles it.
        // But we need categories.
        $this->seedCategories();

        $campaign = new Campaign();
        $campaign->setTitle("Test Campaign");
        $campaign->setUser($user);
        $campaign->setSessionDay(1);
        $campaign->setSessionYear(1105);
        $this->em->persist($campaign);

        $asset = new Asset();
        $asset->setName("ISS Test Trader");
        $asset->setCategory('ship');
        $asset->setUser($user);
        $asset->setCampaign($campaign);
        $asset->setCampaign($campaign);
        $this->em->persist($asset);

        $fa = new \App\Entity\FinancialAccount();
        $fa->setAsset($asset);
        $fa->setUser($user);
        $fa->setCredits(1000000);
        $this->em->persist($fa);
        $this->em->flush();

        // 2. GENERATION
        $session = $this->brokerService->createSession($campaign, 'Spinward Marches', '1905', 2);

        $oppData = new CubeOpportunityData(
            signature: uniqid('SIG_'),
            type: 'TRADE',
            summary: 'Polymers to Lunion',
            amount: 50000.0,
            distance: 2,
            details: [
                'goods' => 'Polymers',
                'tons' => 10,
                'destination' => 'Lunion',
                'origin' => 'Regina',
                'start_day' => 10,
                'start_year' => 1105
            ]
        );

        $opportunity = $this->brokerService->saveOpportunity($session, $oppData->toArray());
        $this->assertNotNull($opportunity->getId(), "Opportunity should be persisted");

        // 3. ACCEPTANCE (BUY)
        $cost = $this->brokerService->acceptOpportunity($opportunity, $asset);

        $this->assertInstanceOf(Cost::class, $cost);
        $this->assertEquals('TRADE', $cost->getCostCategory()->getCode());
        $this->assertEquals($user->getId(), $cost->getUser()->getId());

        // 4. VERIFY UNSOLD
        $repo = $this->em->getRepository(Cost::class);
        $unsold = $repo->findUnsoldTradeGoods($user);
        $found = false;
        foreach ($unsold as $u) {
            if ($u->getId() === $cost->getId()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Cost should be listed as Unsold");

        // 5. LIQUIDATION (SELL)
        $income = new Income();
        $income->setUser($user);
        $income->setFinancialAccount($fa);
        $income->setIncomeCategory($this->em->getRepository(\App\Entity\IncomeCategory::class)->findOneBy(['code' => 'TRADE']));
        $income->setTitle('Sale: ' . $cost->getTitle());
        $income->setAmount('75000');
        $income->setStatus(Income::STATUS_SIGNED);

        // New JSON details approach
        $income->setDetails([
            'goods' => 'Polymers',
            'qty' => 10,
            'purchase_cost_id' => $cost->getId()
        ]);
        $income->setPurchaseCost($cost);
        $income->setDetails([
            'goods' => 'Polymers',
            'qty' => 10,
            'purchase_cost_id' => $cost->getId()
        ]);

        $this->em->persist($income);
        $this->em->flush();

        // 6. VERIFY REMOVAL
        $unsoldAfter = $repo->findUnsoldTradeGoods($user);
        $foundAfter = false;
        foreach ($unsoldAfter as $u) {
            if ($u->getId() === $cost->getId()) {
                $foundAfter = true;
                break;
            }
        }
        $this->assertFalse($foundAfter, "Cost should NO LONGER be listed as Unsold");
    }

    private function seedCategories()
    {
        $costCat = new \App\Entity\CostCategory();
        $costCat->setCode('TRADE');
        $costCat->setDescription('Trade');
        $this->em->persist($costCat);

        $incCat = new \App\Entity\IncomeCategory();
        $incCat->setCode('TRADE');
        $incCat->setDescription('Trade');
        $this->em->persist($incCat);

        $this->em->flush();
    }
}
