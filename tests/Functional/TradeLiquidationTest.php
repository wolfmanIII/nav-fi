<?php

namespace App\Tests\Functional;

use App\Entity\Asset;
use App\Entity\Campaign;
use App\Entity\Cost;
use App\Entity\CostCategory;
use App\Entity\Income;
use App\Entity\IncomeCategory;
use App\Service\Cube\TradeService;
use App\Repository\CostRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TradeLiquidationTest extends KernelTestCase
{
    private $em;
    private $tradeService;
    private $costRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get('doctrine')->getManager();
        $this->tradeService = $container->get(TradeService::class);
        $this->costRepository = $container->get(CostRepository::class);

        // Ensure Categories exist
        if (!$this->em->getRepository(CostCategory::class)->findOneBy(['code' => 'TRADE'])) {
            $cat = new CostCategory();
            $cat->setCode('TRADE'); // Ensure this matches what findUnsold uses
            $cat->setDescription('Trade Purchase');
            $this->em->persist($cat);
        }

        if (!$this->em->getRepository(IncomeCategory::class)->findOneBy(['code' => 'TRADE'])) {
            $cat = new IncomeCategory();
            $cat->setCode('TRADE');
            $cat->setDescription('Trade Sale');
            $this->em->persist($cat);
        }
        $this->em->flush();
    }

    public function testFullLiquidationFlow()
    {
        // 1. Setup Data
        $campaign = new Campaign();
        $campaign->setTitle('Trade Campaign');
        $this->em->persist($campaign);

        $asset = new Asset();
        $asset->setName('Trader Ship');
        $asset->setCampaign($campaign);
        $this->em->persist($asset);
        $this->em->flush();

        // 2. Create "Unsold" Trade Cost
        $cost = new Cost();
        $cost->setAsset($asset);
        $cost->setTitle('Purchase Cargo: 10t Electronics');
        $cost->setAmount('10000');
        $cost->setPaymentDay(100);
        $cost->setPaymentYear(1105);
        $cost->setCostCategory($this->em->getRepository(CostCategory::class)->findOneBy(['code' => 'TRADE']));
        $this->em->persist($cost);
        $this->em->flush();

        // 3. Verify it appears in "Unsold" list
        $unsold = $this->costRepository->findUnsoldTradeCargoForAsset($asset);
        $this->assertCount(1, $unsold);
        $this->assertEquals($cost->getId(), $unsold[0]->getId());

        // 4. Liquidate (Sell)
        $salePrice = 15000.0;
        $income = $this->tradeService->liquidateCargo($cost, $salePrice, 'Regina', 105, 1105);

        // 5. Verify Income details
        $this->assertInstanceOf(Income::class, $income);
        $this->assertEquals('15000', $income->getAmount());
        $this->assertEquals($cost->getId(), $income->getPurchaseCost()->getId());

        // 6. Verify "Unsold" list is now empty (or filtered out)
        // We need to clear EM to ensure query is fresh? findUnsold uses query builder, so it hits DB.
        // But the entities in memory might mask it if not refreshed?
        // Let's rely on the fact that we just persisted the Income which links the Cost.
        $unsoldAfter = $this->costRepository->findUnsoldTradeCargoForAsset($asset);
        $this->assertEmpty($unsoldAfter, 'Cargo should no longer appear as unsold after liquidation.');
    }
}
