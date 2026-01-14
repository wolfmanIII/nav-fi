<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Campaign;
use App\Entity\Cost;
use App\Entity\CostCategory;
use App\Entity\Income;
use App\Entity\IncomeCategory;
use App\Entity\InterestRate;
use App\Entity\Mortgage;
use App\Entity\Ship;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RefereeFlexibilityTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();

        $dbPath = dirname(__DIR__, 2) . '/var/test.db';
        $_SERVER['DATABASE_URL'] = 'sqlite:///' . $dbPath;
        $_ENV['DATABASE_URL'] = $_SERVER['DATABASE_URL'];

        $this->client = static::createClient();
        $this->em = static::getContainer()->get('doctrine')->getManager();

        $schemaTool = new SchemaTool($this->em);
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($this->em->getMetadataFactory()->getAllMetadata());
    }

    public function testCanEditPaidCost(): void
    {
        $user = $this->createUser('referee@test.local');
        $campaign = $this->createCampaign($user);
        $ship = $this->createShip($user, 'ISS Flexibility');
        $ship->setCampaign($campaign);
        $category = $this->createCostCategory('FUEL', 'Fuel');

        $cost = (new Cost())
            ->setUser($user)
            ->setShip($ship)
            ->setCostCategory($category)
            ->setTitle('Paid Fuel Bill')
            ->setAmount('500.00')
            ->setPaymentDay(1)
            ->setPaymentYear(1105)
            ->setDetailItems([['description' => 'Fuel', 'quantity' => 1, 'cost' => 500.00]]);

        $this->em->persist($campaign);
        $this->em->persist($ship);
        $this->em->persist($category);
        $this->em->persist($cost);
        $this->em->flush();

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/cost/edit/' . $cost->getId());
        self::assertResponseIsSuccessful();

        // Verify informational alert is present (Advisory Mode)
        self::assertStringContainsString('Cost is marked as PAID', $crawler->filter('.alert-info')->text());

        // Try to save changes
        $this->client->submit($crawler->filter('button.btn-primary')->form(), [
            'cost[title]' => 'Edited Paid bill',
        ]);

        self::assertResponseRedirects('/cost/index');
        $this->em->clear();

        $updatedCost = $this->em->getRepository(Cost::class)->find($cost->getId());
        self::assertSame('Edited Paid bill', $updatedCost->getTitle());
    }

    public function testCanEditSignedMortgage(): void
    {
        $user = $this->createUser('referee2@test.local');
        $campaign = $this->createCampaign($user);
        $ship = $this->createShip($user, 'ISS Debt');
        $ship->setCampaign($campaign);
        $rate = $this->createInterestRate();

        $mortgage = (new Mortgage())
            ->setUser($user)
            ->setShip($ship)
            ->setInterestRate($rate)
            ->setName('Long Term Loan')
            ->setStartDay(1)
            ->setStartYear(1100)
            ->setSigned(true)
            ->setSigningDay(5)
            ->setSigningYear(1105);

        $this->em->persist($campaign);
        $this->em->persist($ship);
        $this->em->persist($rate);
        $this->em->persist($mortgage);
        $this->em->flush();

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/mortgage/edit/' . $mortgage->getId());
        self::assertResponseIsSuccessful();

        // Verify advisory alert
        self::assertStringContainsString('Mortgage is SIGNED', $crawler->filter('.alert-info')->text());

        // Submit change
        $this->client->submit($crawler->filter('button.btn-primary')->form(), [
            'mortgage[shipShares]' => 10,
        ]);

        $this->em->clear();
        $updatedMortgage = $this->em->getRepository(Mortgage::class)->find($mortgage->getId());
        self::assertSame(10, $updatedMortgage->getShipShares());
    }

    public function testCanEditSignedIncome(): void
    {
        $user = $this->createUser('referee3@test.local');
        $campaign = $this->createCampaign($user);
        $ship = $this->createShip($user, 'ISS Rewards');
        $ship->setCampaign($campaign);
        $category = $this->createIncomeCategory('CONTRACT', 'Contract');

        $income = (new Income())
            ->setUser($user)
            ->setShip($ship)
            ->setIncomeCategory($category)
            ->setTitle('Signed Contract')
            ->setAmount('5000.00')
            ->setPaymentDay(1)
            ->setPaymentYear(1105);

        $this->em->persist($campaign);
        $this->em->persist($ship);
        $this->em->persist($category);
        $this->em->persist($income);
        $this->em->flush();

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/income/edit/' . $income->getId());
        self::assertResponseIsSuccessful();

        self::assertStringContainsString('Income is marked as PAID', $crawler->filter('.alert-info')->text());

        $this->client->submit($crawler->filter('button.btn-primary')->form(), [
            'income[title]' => 'Edited Signed Contract',
        ]);

        self::assertResponseRedirects('/income/index');
        $this->em->clear();

        $updatedIncome = $this->em->getRepository(Income::class)->find($income->getId());
        self::assertSame('Edited Signed Contract', $updatedIncome->getTitle());
    }

    public function testShipDeletionCascades(): void
    {
        $user = $this->createUser('referee3@test.local');
        $ship = $this->createShip($user, 'ISS Expendable');
        $rate = $this->createInterestRate();

        $mortgage = (new Mortgage())
            ->setUser($user)
            ->setShip($ship)
            ->setInterestRate($rate)
            ->setName('To Be Deleted')
            ->setStartDay(1)
            ->setStartYear(1100);

        $cost = (new Cost())
            ->setUser($user)
            ->setShip($ship)
            ->setCostCategory($this->createCostCategory('MISC', 'Misc'))
            ->setTitle('Ghost Cost')
            ->setAmount('10.00')
            ->setDetailItems([['description' => 'Misc', 'quantity' => 1, 'cost' => 10.00]]);

        $ship->setMortgage($mortgage);
        $ship->addCost($cost);

        $this->em->persist($ship);
        $this->em->persist($rate);
        $this->em->persist($mortgage);
        $this->em->persist($cost);
        $this->em->flush();

        $shipId = $ship->getId();
        $mortgageId = $mortgage->getId();
        $costId = $cost->getId();

        $this->client->loginUser($user);

        // Use direct EM remove to test database cascade configuration
        $this->em->remove($ship);
        $this->em->flush();

        $this->em->clear();
        self::assertNull($this->em->getRepository(Ship::class)->find($shipId));
        self::assertNull($this->em->getRepository(Mortgage::class)->find($mortgageId));
        self::assertNull($this->em->getRepository(Cost::class)->find($costId));
    }

    public function testTimelineInconsistencyWarning(): void
    {
        $user = $this->createUser('referee4@test.local');
        $campaign = $this->createCampaign($user);

        $ship = $this->createShip($user, 'ISS Time Traveler')
            ->setCampaign($campaign);

        $category = $this->createCostCategory('SUPPLY', 'Supplies');

        // Cost date BEFORE campaign date (alert should trigger)
        // Campaign is 10/1105. Let's set cost to 5/1105.
        $cost = (new Cost())
            ->setUser($user)
            ->setShip($ship)
            ->setCostCategory($category)
            ->setTitle('Old Bill')
            ->setAmount('100.00')
            ->setPaymentDay(5)
            ->setPaymentYear(1105)
            ->setDetailItems([['description' => 'Stuff', 'quantity' => 1, 'cost' => 100.00]]);

        $this->em->persist($campaign);
        $this->em->persist($ship);
        $this->em->persist($category);
        $this->em->persist($cost);
        $this->em->flush();

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/cost/edit/' . $cost->getId());

        self::assertResponseIsSuccessful();
        // Index != Session -> Chronological Data Verification
        self::assertStringContainsString('Chronological Data Verification', $crawler->filter('body')->text());

        // Test with another date discrepancy
        $cost->setPaymentDay(15);
        $this->em->flush();
        $crawler = $this->client->request('GET', '/cost/edit/' . $cost->getId());
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Chronological Data Verification', $crawler->filter('body')->text());
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
            ->setPrice('1000000.00');
    }

    private function createCostCategory(string $code, string $description): CostCategory
    {
        $cat = (new CostCategory())->setCode($code)->setDescription($description);
        $this->em->persist($cat);
        return $cat;
    }

    private function createInterestRate(): InterestRate
    {
        $rate = (new InterestRate())
            ->setPriceMultiplier('1.2')
            ->setPriceDivider(1)
            ->setAnnualInterestRate('4.0')
            ->setDuration(120);
        $this->em->persist($rate);
        return $rate;
    }

    private function createIncomeCategory(string $code, string $description): IncomeCategory
    {
        $cat = (new IncomeCategory())->setCode($code)->setDescription($description);
        $this->em->persist($cat);
        return $cat;
    }

    private function createCampaign(User $user): Campaign
    {
        $campaign = (new Campaign())
            ->setUser($user)
            ->setTitle('Test Campaign')
            ->setSessionDay(10)
            ->setSessionYear(1105)
            ->setStartingYear(1100);
        $this->em->persist($campaign);
        return $campaign;
    }
}
