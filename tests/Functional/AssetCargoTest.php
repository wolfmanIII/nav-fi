<?php

namespace App\Tests\Functional;

use App\Entity\Asset;
use App\Entity\Campaign;
use App\Entity\Cost;
use App\Entity\CostCategory;
use App\Entity\FinancialAccount;
use App\Entity\LocalLaw;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AssetCargoTest extends WebTestCase
{
    private $client;
    private $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = self::getContainer()->get('doctrine')->getManager();

        // Ensure Categories exist
        if (!$this->em->getRepository(CostCategory::class)->findOneBy(['code' => 'TRADE'])) {
            $cat = new CostCategory();
            $cat->setCode('TRADE');
            $cat->setDescription('Trade');
            $this->em->persist($cat);
        }

        if (!$this->em->getRepository(\App\Entity\IncomeCategory::class)->findOneBy(['code' => 'TRADE'])) {
            $cat = new \App\Entity\IncomeCategory();
            $cat->setCode('TRADE');
            $cat->setDescription('Trade Sale');
            $this->em->persist($cat);
        }

        if (!$this->em->getRepository(LocalLaw::class)->findOneBy(['code' => 'TEST_LAW'])) {
            $law = new LocalLaw();
            $law->setCode('TEST_LAW');
            $law->setShortDescription('Test Law');
            $law->setDescription('Full Test Law Description');
            $this->em->persist($law);
        }

        $this->em->flush();
    }

    public function testAddLootAndSell()
    {
        // 1. Setup Data
        $user = new User();
        $user->setEmail('cargo_' . uniqid() . '@test.com');
        $user->setPassword('hash');
        $this->em->persist($user);

        $campaign = new Campaign();
        $campaign->setTitle('Cargo Campaign');
        $campaign->setSessionDay(10);
        $campaign->setSessionYear(1105);
        $campaign->setUser($user);
        $this->em->persist($campaign);

        $asset = new Asset();
        $asset->setName('Test Ship');
        $asset->setCategory(Asset::CATEGORY_SHIP);
        $asset->setUser($user);
        $asset->setCampaign($campaign);
        $this->em->persist($asset);

        $account = new FinancialAccount();
        $account->setAsset($asset);
        $account->setUser($user);
        $account->setCredits('100000');
        $this->em->persist($account);

        $this->em->flush();

        $this->client->loginUser($user);

        // 2. Add Loot
        $crawler = $this->client->request('GET', "/asset/{$asset->getId()}/cargo");
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Add to Manifest')->form();
        $form['cargo_loot[title]'] = 'Ancient Relics';
        $form['cargo_loot[quantity]'] = '5';
        $form['cargo_loot[unitPrice]'] = '1000';
        $form['cargo_loot[origin]'] = 'Deep Space';
        $form['cargo_loot[localLaw]'] = $this->em->getRepository(LocalLaw::class)->findOneBy(['code' => 'TEST_LAW'])->getId();

        $this->client->submit($form);
        $this->assertResponseRedirects("/asset/{$asset->getId()}/cargo");
        $this->client->followRedirect();

        // Verify Cost Created
        $cost = $this->em->getRepository(Cost::class)->findOneBy(['title' => 'Ancient Relics', 'financialAccount' => $account]);
        $this->assertNotNull($cost);
        $this->assertEquals('0', $cost->getAmount()); // Loot is free
        $this->assertEquals('Deep Space', $cost->getTargetDestination());

        // 3. Sell Loot
        // The page generates multiple liquidation forms. We need to find the one for our item.
        $crawler = $this->client->request('GET', "/asset/{$asset->getId()}/cargo");
        $form = $crawler->filter("form[name='liquidation_{$cost->getId()}']")->form();
        $form["liquidation_{$cost->getId()}[location]"] = 'Starport Alpha';
        $form["liquidation_{$cost->getId()}[localLaw]"] = $this->em->getRepository(LocalLaw::class)->findOneBy(['code' => 'TEST_LAW'])->getId();

        $this->client->submit($form);
        $this->assertResponseRedirects("/asset/{$asset->getId()}/cargo");
        $this->client->followRedirect();

        // Verify Income Created
        $income = $this->em->getRepository(\App\Entity\Income::class)->findOneBy(['purchaseCost' => $cost]);
        $this->assertNotNull($income);
        $this->assertGreaterThan(0, $income->getAmount());
    }
}
