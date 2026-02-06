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

        // Usa un DB separato per questo test funzionale per evitare conflitti
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
        // 1. Setup dati
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
        $role->setCode('BANK');
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

        $financialAccount = new \App\Entity\FinancialAccount();
        $financialAccount->setUser($user);
        $financialAccount->setAsset($asset);
        $this->em->persist($financialAccount);

        $this->em->flush();

        // 2. Crea mutuo via UI
        $crawler = $this->client->request('GET', '/mortgage/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Confirm Allocation // Commit to Log')->form([
            'mortgage[asset]' => $asset->getId(),
            'mortgage[campaign]' => $campaign->getId(), // mapped=false ma usato per logica filtro? No, lo aggiunge il costruttore del form.
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

        // 3. Verifica che il mutuo esista
        $mortgage = $this->em->getRepository(Mortgage::class)->findOneBy(['name' => 'MOR - Freighter M']);
        self::assertNotNull($mortgage);
        self::assertEquals(100000.00, (float) $mortgage->getAdvancePayment());

        // Firma manualmente per abilitare il pagamento (bypass del flusso UI per brevità)
        // $mortgage->setSigned(true); // Metodo inesistente, derivato dalla data
        $mortgage->setSigningDay(100);
        $mortgage->setSigningYear(1105);
        $this->em->flush();

        // 4. Paga rata
        $crawler = $this->client->request('GET', '/mortgage/edit/' . $mortgage->getId());
        self::assertResponseIsSuccessful();

        // Aggiornamento: usa selectButton sulla pagina di modifica (che contiene il form modale)
        $form = $crawler->selectButton('Execute Transfer')->form([
            'mortgage_installment[paymentDate][day]' => 105,
            'mortgage_installment[paymentDate][year]' => 1105,
            // 'mortgage_installment[payment]' => '865.38', // Sola lettura, potrebbe essere ignorato o non necessario se lo calcola la logica?
            // Ma se vogliamo essere sicuri:
            'mortgage_installment[payment]' => '865.38',
        ]);

        $this->client->submit($form);

        self::assertResponseRedirects('/mortgage/edit/' . $mortgage->getId());

        // 5. Verifica transazione nel libro mastro (atteso fallimento al momento)
        $transaction = $this->em->getRepository(Transaction::class)->findOneBy([
            'relatedEntityId' => $mortgage->getMortgageInstallments()->last()->getId(), // Prendi l'ultima rata creata
            'relatedEntityType' => 'MortgageInstallment',
        ]);

        // Per ora, verifichiamo che esista.
        self::assertNotNull($transaction, 'Transaction should exist for Mortgage Installment Payment');
        self::assertEquals(-865.38, (float) $transaction->getAmount());
    }

    // Supporto per ottenere il token CSRF se necessario, o usare il crawler.
    // Userò il crawler per trovare il form nella pagina di modifica.
    // Se non riesco a trovarlo, fallirò.

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('mortgage@test.com');
        $user->setPassword('$2y$13$thmMo1c1o..'); // hash della password
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    private function login(User $user): void
    {
        $this->client->loginUser($user);
    }
}
