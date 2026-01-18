<?php

namespace App\Tests\Functional;

use App\Entity\Asset;
use App\Entity\Campaign;
use App\Entity\Company;
use App\Entity\CompanyRole;
use App\Entity\Insurance;
use App\Entity\InterestRate;
use App\Entity\LocalLaw;
use App\Entity\Mortgage;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MortgageFlowTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Use a separate DB for this functional test to avoid conflicts
        $dbPath = dirname(__DIR__, 2) . '/var/test_mortgage_flow.db';
        if (is_file($dbPath)) {
            unlink($dbPath);
        }

        $_SERVER['DATABASE_URL'] = 'sqlite:///' . $dbPath;
        $_ENV['DATABASE_URL'] = $_SERVER['DATABASE_URL'];

        $this->em = static::getContainer()->get('doctrine')->getManager();

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);
    }

    public function testMortgageCreationAndPayment(): void
    {
        // 1. Setup Data
        $user = $this->createUser();
        $this->login($user);

        $campaign = new Campaign();
        $campaign->setTitle('Campaign M');
        $campaign->setSessionDay(100);
        $campaign->setSessionYear(1105);
        $campaign->setUser($user);
        $this->em->persist($campaign);

        $role = new CompanyRole();
        $role->setDescription('MegaCorp');
        $role->setCode('MC');
        $this->em->persist($role);

        $company = new Company();
        $company->setName('Bank of Sol');
        $company->setCompanyRole($role);
        $company->setUser($user);
        $this->em->persist($company);

        $law = new LocalLaw();
        $law->setCode('Law-1');
        $law->setDescription('Standard Law');
        $this->em->persist($law);

        $rate = new InterestRate();
        $rate->setDuration(240);
        $rate->setPriceMultiplier('1.0');
        $rate->setPriceDivider(1);
        $rate->setAnnualInterestRate('5.00');
        $this->em->persist($rate);

        $insurance = new Insurance();
        $insurance->setName('Standard Ins');
        $insurance->setAnnualCost(1); // 1%
        $this->em->persist($insurance);

        $asset = new Asset();
        $asset->setName('Freighter M');
        $asset->setCategory(Asset::CATEGORY_SHIP);
        $asset->setPrice('1000000.00');
        $asset->setCampaign($campaign);
        $asset->setUser($user);
        $this->em->persist($asset);

        $this->em->flush();

        // 2. Create Mortgage via UI
        $crawler = $this->client->request('GET', '/mortgage/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Confirm Allocation // Commit to Log')->form([
            'mortgage[asset]' => $asset->getId(),
            'mortgage[campaign]' => $campaign->getId(), // Mapped=false but used for query filter logic? No, form builder adds it.
            'mortgage[company]' => $company->getId(),
            'mortgage[localLaw]' => $law->getId(),
            'mortgage[interestRate]' => $rate->getId(),
            'mortgage[insurance]' => $insurance->getId(),
            'mortgage[assetShares]' => 0,
            'mortgage[advancePayment]' => '100000.00',
            'mortgage[discount]' => 0,
        ]);
        $this->client->submit($form);
        self::assertResponseRedirects('/mortgage/index');
        $this->client->followRedirect();

        // 3. Verify Mortgage Exists
        $mortgage = $this->em->getRepository(Mortgage::class)->findOneBy(['name' => 'MOR - Freighter M']);
        self::assertNotNull($mortgage);
        self::assertEquals(100000.00, (float) $mortgage->getAdvancePayment());

        // Manually sign it to enable payment (bypass UI signing flow for brevity)
        // $mortgage->setSigned(true); // Method does not exist, derived from date
        $mortgage->setSigningDay(100);
        $mortgage->setSigningYear(1105);
        $this->em->flush();

        // 4. Pay Installment
        $crawler = $this->client->request('GET', '/mortgage/edit/' . $mortgage->getId());
        self::assertResponseIsSuccessful();

        // Update: Use selectButton on the edit page (which contains the modal form)
        $form = $crawler->selectButton('Execute Transfer')->form([
            'mortgage_installment[paymentDate][day]' => 105,
            'mortgage_installment[paymentDate][year]' => 1105,
            // 'mortgage_installment[payment]' => '865.38', // Readonly, might be ignored or not needed if logic calculates it?
            // But if we want to be sure:
            'mortgage_installment[payment]' => '865.38',
        ]);

        $this->client->submit($form);

        self::assertResponseRedirects('/mortgage/edit/' . $mortgage->getId());

        // 5. Verify Transaction in Ledger (Expected to FAIL currently)
        $transaction = $this->em->getRepository(Transaction::class)->findOneBy([
            'relatedEntityId' => $mortgage->getMortgageInstallments()->last()->getId(), // Get the last installment created
            'relatedEntityType' => 'MortgageInstallment',
        ]);

        // For now, let's assert it exists.
        self::assertNotNull($transaction, 'Transaction should exist for Mortgage Installment Payment');
        self::assertEquals(-865.38, (float) $transaction->getAmount());
    }

    // Helper to get CSRF token if needed, or stick to crawler.
    // I'll stick to crawler finding the form on Edit page.
    // If I can't finding it, I'll fail.

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('mortgage@test.com');
        $user->setPassword('$2y$13$thmMo1c1o..'); // hash
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    private function login(User $user): void
    {
        $this->client->loginUser($user);
    }
}
