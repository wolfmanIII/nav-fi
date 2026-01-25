<?php

namespace App\Tests\Service\Workflow;

use App\Entity\Cost;
use App\Entity\Income;
use App\Entity\CostCategory;
use App\Entity\IncomeCategory;
use App\Entity\Asset;
use App\Entity\User;
use App\Entity\Company;
use App\Repository\CostRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TradeWorkflowTest extends KernelTestCase
{
    private $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get('doctrine')->getManager();

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();

        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testUnsoldGoodsQuery(): void
    {
        // 1. Setup Data
        $user = new User();
        $user->setEmail('trade_test_' . uniqid() . '@example.com');
        $user->setPassword('password');
        $this->em->persist($user);

        // Cost Category TRADE (Mock or Fetch)
        $costCat = new CostCategory();
        $costCat->setCode('TRADE');
        $costCat->setDescription('Trade');
        $this->em->persist($costCat);

        $asset = new Asset();
        $asset->setName('Merchant Ship');
        $asset->setUser($user);
        $this->em->persist($asset);

        // 2. Create Unsold Cost
        $cost = new Cost();
        $cost->setUser($user);
        $cost->setAsset($asset);
        $cost->setCostCategory($costCat);
        $cost->setTitle('Test Cargo');
        $cost->setAmount('1000');
        $this->em->persist($cost);

        $this->em->flush();

        // 3. Verify Query finds it
        /** @var CostRepository $repo */
        $repo = $this->em->getRepository(Cost::class);
        $unsold = $repo->findUnsoldTradeGoods($user);

        $this->assertCount(1, $unsold);
        $this->assertEquals('Test Cargo', $unsold[0]->getTitle());
    }
}
