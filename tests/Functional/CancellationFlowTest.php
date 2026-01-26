<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Asset;
use App\Entity\Campaign;
use App\Entity\Income;
use App\Entity\IncomeCategory;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CancellationFlowTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $dbPath = dirname(__DIR__, 2) . '/var/test_cancel.db';
        if (is_file($dbPath)) {
            unlink($dbPath);
        }

        $_SERVER['DATABASE_URL'] = 'sqlite:///' . $dbPath;
        $_ENV['DATABASE_URL'] = $_SERVER['DATABASE_URL'];

        $this->client = static::createClient();
        $this->em = static::getContainer()->get('doctrine')->getManager();

        $connection = $this->em->getConnection();
        if (!Type::hasType('uuid')) {
            Type::addType('uuid', StringType::class);
        }
        $connection->getDatabasePlatform()->registerDoctrineTypeMapping('uuid', 'string');

        $schemaTool = new SchemaTool($this->em);
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($this->em->getMetadataFactory()->getAllMetadata());
    }

    public function testIncomeCancellationFlow(): void
    {
        // 1. Setup Utente, Asset, Campagna
        $user = $this->createUser('captain@cancel.test');
        $this->login($user);

        $campaign = new Campaign();
        $campaign->setTitle('Test Campaign');
        $campaign->setSessionDay(100);
        $campaign->setSessionYear(1105);
        $this->em->persist($campaign);

        $asset = new Asset();
        $asset->setUser($user);
        $asset->setName('ISS Reliable');
        $asset->setType('Trader');
        $asset->setClass('A-1');
        $asset->setPrice('1000000.00');
        $asset->setCampaign($campaign);
        $this->em->persist($asset);

        $fa = new \App\Entity\FinancialAccount();
        $fa->setAsset($asset);
        $fa->setUser($user);
        $this->em->persist($fa);

        $category = new IncomeCategory();
        $category->setCode('CONTRACT');
        $category->setDescription('Contract');
        $this->em->persist($category);

        $this->em->flush();

        // 2. Crea Income attivo
        $crawler = $this->client->request('GET', '/income/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Confirm Entry // Commit to Log')->form([
            'income[title]' => 'Future Job',
            'income[amount]' => '5000.00',
            'income[signingDate][day]' => 100,
            'income[signingDate][year]' => 1105,
            'income[paymentDate][day]' => 120, // Pagamento futuro
            'income[paymentDate][year]' => 1105,
            'income[financialAccount]' => $fa->getId(),
            'income[incomeCategory]' => $category->getId(),
        ]);
        $this->client->submit($form);
        self::assertResponseRedirects('/income/index');
        $this->client->followRedirect();

        // 3. Verifica Income creato e libro mastro in Pending
        $income = $this->em->getRepository(Income::class)->findOneBy(['title' => 'Future Job']);
        self::assertNotNull($income);
        self::assertNotSame(Income::STATUS_CANCELLED, $income->getStatus());

        $transaction = $this->em->getRepository(Transaction::class)->findOneBy([
            'relatedEntityId' => $income->getId(),
            'relatedEntityType' => 'Income'
        ]);
        self::assertNotNull($transaction);
        self::assertSame(Transaction::STATUS_PENDING, $transaction->getStatus());

        // 4. Cancella l'Income
        $crawler = $this->client->request('GET', '/income/edit/' . $income->getId());
        self::assertResponseIsSuccessful();

        // Verifica che l'avviso di cancellazione sia inizialmente assente
        self::assertSame(0, $crawler->filter('div:contains("Tactical Alert: Asset Income Voided")')->count());

        $form = $crawler->selectButton('Confirm Entry // Commit to Log')->form();
        $form['income[cancelDate][day]'] = 110;
        $form['income[cancelDate][year]'] = 1105;

        $this->client->submit($form);
        self::assertResponseRedirects('/income/index');

        // 5. Verifica aggiornamenti di stato
        $this->em->clear();
        $this->em->getConnection()->close();
        $this->em->getConnection()->connect();

        $updatedIncome = $this->em->getRepository(Income::class)->find($income->getId());
        self::assertSame(Income::STATUS_CANCELLED, $updatedIncome->getStatus());

        // Verifica che abbiamo:
        // 1. Pending originale (invertito, ma ancora presente se non assertiamo la logica di inversione che crea solo una tx di correzione)
        // 2. Correzione/Inversione
        // 3. Nuova transazione Void

        $voidTx = $this->em->getRepository(Transaction::class)->findOneBy([
            'relatedEntityId' => $income->getId(),
            'relatedEntityType' => 'Income',
            'status' => Transaction::STATUS_VOID
        ]);
        self::assertNotNull($voidTx, 'Void Transaction should exist');

        // Reload User to ensure session consistency after clear
        $user = $this->em->getRepository(User::class)->find($user->getId());
        $this->client->loginUser($user);

        // 6. Verifica presenza della filigrana UI rientrando in modifica
        $crawler = $this->client->request('GET', '/income/edit/' . $income->getId());
        // Verifica lo snippet di testo dell'alert
        self::assertStringContainsString('Tactical Alert: Asset Income Voided', $crawler->html());
    }

    private function login(User $user): void
    {
        $this->client->loginUser($user);
    }

    private function createUser(string $email): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setRoles(['ROLE_USER'])
            ->setPassword('hash');

        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }
}
