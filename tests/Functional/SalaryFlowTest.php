<?php

namespace App\Tests\Functional;

use App\Entity\Asset;
use App\Entity\Campaign;
use App\Entity\Crew;
use App\Entity\Salary;
use App\Entity\SalaryPayment;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SalaryFlowTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        // Usa un DB separato per questo test funzionale per evitare conflitti
        $dbPath = dirname(__DIR__, 2) . '/var/test_salary_flow.db';
        if (is_file($dbPath)) {
            unlink($dbPath);
        }

        $_SERVER['DATABASE_URL'] = 'sqlite:///' . $dbPath;
        $_ENV['DATABASE_URL'] = $_SERVER['DATABASE_URL'];

        $this->client = static::createClient();

        $this->em = static::getContainer()->get('doctrine')->getManager();

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);
    }

    public function testSalaryCreationFlow(): void
    {
        // 1. Setup dati
        $user = $this->createUser();
        $this->login($user);

        $campaign = new Campaign();
        $campaign->setTitle('Test Campaign');
        $campaign->setSessionDay(100);
        $campaign->setSessionYear(1105);
        $campaign->setUser($user);
        $this->em->persist($campaign);

        $asset = new Asset();
        $asset->setName('Test Ship');
        $asset->setCategory(Asset::CATEGORY_SHIP);
        $asset->setPrice('500000.00');
        $asset->setCampaign($campaign);
        $asset->setUser($user);
        $this->em->persist($asset);

        $crew = new Crew();
        $crew->setName('Jan');
        $crew->setSurname('Doe');
        $crew->setAsset($asset);
        $crew->setStatus(Crew::STATUS_ACTIVE);
        $crew->setActiveDay(50);
        $crew->setActiveYear(1105);
        $this->em->persist($crew);

        $this->em->flush();

        // 2. Crea stipendio via UI
        $crawler = $this->client->request('GET', '/salary/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Confirm Agreement // Registry Update')->form([
            'salary[campaign]' => $campaign->getId(),
            'salary[asset]' => $asset->getId(),
            'salary[crew]' => $crew->getId(),
            'salary[amount]' => '2500.00',
            'salary[status]' => Salary::STATUS_ACTIVE,
            'salary[firstPaymentDate][day]' => 56, // Giorno di pagamento globale
            'salary[firstPaymentDate][year]' => 1105,
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/salary/index');
        $this->client->followRedirect();

        // 3. Verifica che lo stipendio esista
        /** @var Salary $salary */
        $salary = $this->em->getRepository(Salary::class)->findOneBy(['crew' => $crew]);
        self::assertNotNull($salary);
        self::assertEquals(2500.00, (float) $salary->getAmount());
        self::assertEquals(56, $salary->getFirstPaymentDay());
        self::assertEquals(1105, $salary->getFirstPaymentYear());

        // 4. Verifica integrazione libro mastro (SalaryPayment -> Transaction)
        $payment = new SalaryPayment();
        $payment->setSalary($salary);
        $payment->setAmount('2500.00');
        $payment->setPaymentDay(56);
        $payment->setPaymentYear(1105);
        $this->em->persist($payment);
        $this->em->flush();

        // Verifica che la transazione sia stata creata da FinancialEventSubscriber
        $transaction = $this->em->getRepository(Transaction::class)->findOneBy([
            'relatedEntityId' => $payment->getId(),
            'relatedEntityType' => 'SalaryPayment',
        ]);

        self::assertNotNull($transaction, 'Transaction should be automatically created for SalaryPayment');
        self::assertEquals(-2500.00, (float) $transaction->getAmount());
        self::assertEquals($asset->getId(), $transaction->getAsset()->getId());
        self::assertEquals('Salary Payment: Jan Doe (Date: 056-1105)', $transaction->getDescription());
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('salary_test@example.com');
        $user->setPassword('$2y$13$thmMo1c1o..'); // Password mock
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    private function login(User $user): void
    {
        $this->client->loginUser($user);
    }
}
