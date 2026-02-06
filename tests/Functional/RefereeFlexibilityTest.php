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
use App\Entity\Asset;
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
        $asset = $this->createAsset($user, 'ISS Flexibility');
        $asset->setCampaign($campaign);
        $category = $this->createCostCategory('FUEL', 'Fuel');

        $cost = (new Cost())
            ->setUser($user)
            ->setFinancialAccount($asset->getFinancialAccount())
            ->setCostCategory($category)
            ->setTitle('Paid Fuel Bill')
            ->setAmount('500.00')
            ->setPaymentDay(1)
            ->setPaymentYear(1105)
            // ->setVendorName('Fuel Depot') // Not mapped in entity
            ->setDetailItems([['description' => 'Fuel', 'quantity' => 1, 'cost' => 500.00]]);

        $role = new \App\Entity\CompanyRole();
        $role->setCode('VENDOR');
        $role->setDescription('Vendor');
        $this->em->persist($role);

        $vendor = new \App\Entity\Company();
        $vendor->setName('Fuel Depot');
        $vendor->setCode('FUEL_DEPOT');
        $vendor->setUser($user);
        $vendor->setCompanyRole($role);
        $cost->setCompany($vendor);

        $this->em->persist($campaign);
        $this->em->persist($asset);
        $this->em->persist($category);
        $this->em->persist($vendor);
        $this->em->persist($cost);
        $this->em->flush();

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/cost/edit/' . $cost->getId());
        self::assertResponseIsSuccessful();

        // Verifica che l'alert informativo sia presente (modalità avviso)
        self::assertStringContainsString('Cost is marked as PAID', $crawler->filter('.alert-info')->text());

        // Prova a salvare le modifiche
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
        $asset = $this->createAsset($user, 'ISS Debt');
        $asset->setCampaign($campaign);
        $rate = $this->createInterestRate();

        $mortgage = (new Mortgage())
            ->setUser($user)
            ->setAsset($asset)
            ->setFinancialAccount($asset->getFinancialAccount())
            ->setInterestRate($rate)
            ->setName('Long Term Loan')
            ->setStartDay(1)
            ->setStartYear(1100)

            ->setSigningDay(5)
            ->setSigningYear(1105);

        $insurance = new \App\Entity\Insurance();
        $insurance->setName('Standard Hull');
        $insurance->setAnnualCost('5'); // 5%
        $this->em->persist($insurance);

        $roleBank = new \App\Entity\CompanyRole();
        $roleBank->setCode('BANK');
        $roleBank->setDescription('Bank');
        $this->em->persist($roleBank);

        $bank = new \App\Entity\Company();
        $bank->setName('Imperial Bank');
        $bank->setCode('IMP_BANK');
        $bank->setUser($user);
        $bank->setCompanyRole($roleBank);
        $this->em->persist($bank);

        $mortgage->setInsurance($insurance);
        $mortgage->setCompany($bank);

        $this->em->persist($campaign);
        $this->em->persist($asset);
        $this->em->persist($rate);
        $this->em->persist($mortgage);
        $this->em->flush();

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/mortgage/edit/' . $mortgage->getId());
        self::assertResponseIsSuccessful();

        // Verifica alert di avviso
        self::assertStringContainsString('Mortgage is SIGNED', $crawler->filter('.alert-info')->text());

        // Invia la modifica
        $this->client->submit($crawler->filter('button.btn-primary')->form(), [
            'mortgage[assetShares]' => 10,
        ]);

        $this->em->clear();
        $updatedMortgage = $this->em->getRepository(Mortgage::class)->find($mortgage->getId());
        self::assertSame(10, $updatedMortgage->getAssetShares());
    }

    public function testCanEditSignedIncome(): void
    {
        $user = $this->createUser('referee3@test.local');
        $campaign = $this->createCampaign($user);
        $asset = $this->createAsset($user, 'ISS Rewards');
        $asset->setCampaign($campaign);
        $asset->setCampaign($campaign);
        $category = $this->createIncomeCategory('CONTRACT', 'Contract');

        // Ensure a CompanyRole exists for the form submission
        $role = new \App\Entity\CompanyRole();
        $role->setCode('PATRON');
        $role->setDescription('Patron');
        $role->setShortDescription('Patron');
        $this->em->persist($role);
        $this->em->flush(); // Need ID for submit

        $income = (new Income())
            ->setUser($user)
            ->setFinancialAccount($asset->getFinancialAccount())
            ->setIncomeCategory($category)
            ->setTitle('Signed Contract')
            ->setAmount('5000.00')
            ->setStatus(Income::STATUS_SIGNED)
            ->setPaymentDay(1)
            ->setPaymentDay(1)
            ->setPaymentYear(1105)
            ->setPatronAlias('Local Gov');

        $this->em->persist($campaign);
        $this->em->persist($asset);
        $this->em->persist($category);
        $this->em->persist($income);
        $this->em->persist($income);
        $this->em->flush();
        $this->em->clear();
        $user = $this->em->getRepository(User::class)->find($user->getId());

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/income/edit/' . $income->getId());
        self::assertResponseIsSuccessful();

        self::assertStringContainsString('Income is marked as PAID', $crawler->filter('.alert-info')->text());

        $this->client->submit($crawler->filter('button.btn-primary')->form(), [
            'income[title]' => 'Edited Signed Contract',
            // Role is required when provided Alias, but here we provided Alias in setup.
            // The form renders with Alias filled. We must provide Role because it's required if Alias is present.
            // We need a valid role ID. Let's create one or pick one.
            // However, IncomeType uses EntityType for role.
            // We need to pass the ID.
            'income[payerCompanyRole]' => 1,
        ]);

        self::assertResponseRedirects('/income/index');
        $this->em->clear();

        $updatedIncome = $this->em->getRepository(Income::class)->find($income->getId());
        self::assertSame('Edited Signed Contract', $updatedIncome->getTitle());
    }

    public function testAssetDeletionCascades(): void
    {
        $user = $this->createUser('referee3@test.local');
        $asset = $this->createAsset($user, 'ISS Expendable');
        $rate = $this->createInterestRate();

        $mortgage = (new Mortgage())
            ->setUser($user)
            ->setAsset($asset)
            ->setFinancialAccount($asset->getFinancialAccount())
            ->setInterestRate($rate)
            ->setName('To Be Deleted')
            ->setStartDay(1)
            ->setStartYear(1100);

        $cost = (new Cost())
            ->setUser($user)
            ->setFinancialAccount($asset->getFinancialAccount())
            ->setCostCategory($this->createCostCategory('MISC', 'Misc'))
            ->setTitle('Ghost Cost')
            // ->setVendorName('Ghost Vendor')
            ->setAmount('10.00')
            ->setDetailItems([['description' => 'Misc', 'quantity' => 1, 'cost' => 10.00]]);

        // Ensure a role exists - might reuse if available, but safe to create unique for test isolation
        $roleGhost = new \App\Entity\CompanyRole();
        $roleGhost->setCode('GHOST_ROLE');
        $roleGhost->setDescription('Ghost Vendor');
        $this->em->persist($roleGhost);

        $vendor = new \App\Entity\Company();
        $vendor->setName('Ghost Vendor');
        $vendor->setCode('GHOST');
        $vendor->setUser($user);
        $vendor->setCompanyRole($roleGhost);
        $cost->setCompany($vendor);

        $asset->setMortgage($mortgage);
        // $asset->addCost($cost); // Removed in refactor

        $this->em->persist($asset);
        $this->em->persist($rate);
        $this->em->persist($mortgage);
        $this->em->persist($vendor);
        $this->em->persist($cost);
        $this->em->flush();

        $assetId = $asset->getId();
        $mortgageId = $mortgage->getId();
        $costId = $cost->getId();

        $this->client->loginUser($user);

        // Usa remove diretto su EM per testare la configurazione di cascata del DB
        // Ricarica asset dal DB per assicurare che le relazioni inverse siano caricate (?)
        // $asset = $this->em->getRepository(Asset::class)->find($assetId);
        // O semplicemente refresh
        // $this->em->refresh($asset); 
        // Ma FinancialAccount è Fetch Lazy o Eager? BasicEntityPersister...

        $this->em->remove($asset);
        $this->em->flush();

        $this->em->clear();
        self::assertNull($this->em->getRepository(Asset::class)->find($assetId));
        self::assertNull($this->em->getRepository(Mortgage::class)->find($mortgageId));
        self::assertNull($this->em->getRepository(Cost::class)->find($costId));
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

    private function createAsset(User $user, string $name): Asset
    {
        $asset = (new Asset())
            ->setUser($user)
            ->setName($name)
            ->setType('Trader')
            ->setClass('A-1')
            ->setPrice('1000000.00');
        $this->em->persist($asset);

        $fa = new \App\Entity\FinancialAccount();
        $fa->setAsset($asset);
        $fa->setUser($user);
        $this->em->persist($fa); // Ensure creation

        return $asset;
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
