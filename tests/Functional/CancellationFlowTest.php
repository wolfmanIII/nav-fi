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
        // 1. Setup User, Asset, Campaign
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

        $category = new IncomeCategory();
        $category->setCode('CONTRACT');
        $category->setDescription('Contract');
        $this->em->persist($category);

        $this->em->flush();

        // 2. Create Active Income
        $crawler = $this->client->request('GET', '/income/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Confirm Entry // Commit to Log')->form([
            'income[title]' => 'Future Job',
            'income[amount]' => '5000.00',
            'income[signingDate][day]' => 100,
            'income[signingDate][year]' => 1105,
            'income[paymentDate][day]' => 120, // Future payment
            'income[paymentDate][year]' => 1105,
            'income[asset]' => $asset->getId(),
            'income[incomeCategory]' => $category->getId(),
        ]);
        $this->client->submit($form);
        self::assertResponseRedirects('/income/index');
        $this->client->followRedirect();

        // 3. Verify Income Created and Ledger Pending
        $income = $this->em->getRepository(Income::class)->findOneBy(['title' => 'Future Job']);
        self::assertNotNull($income);
        self::assertNotSame(Income::STATUS_CANCELLED, $income->getStatus());

        $transaction = $this->em->getRepository(Transaction::class)->findOneBy([
            'relatedEntityId' => $income->getId(),
            'relatedEntityType' => 'Income'
        ]);
        self::assertNotNull($transaction);
        self::assertSame(Transaction::STATUS_PENDING, $transaction->getStatus());

        // 4. Cancel the Income
        $crawler = $this->client->request('GET', '/income/edit/' . $income->getId());
        self::assertResponseIsSuccessful();

        // Check if Cancellation warning is initially absent
        self::assertSame(0, $crawler->filter('div:contains("Tactical Alert: Asset Income Voided")')->count());

        $form = $crawler->selectButton('Confirm Entry // Commit to Log')->form();
        $form['income[cancelDate][day]'] = 110;
        $form['income[cancelDate][year]'] = 1105;

        $this->client->submit($form);
        self::assertResponseRedirects('/income/index');

        // 5. Verify Status Updates
        $this->em->clear();
        $this->em->getConnection()->close();
        $this->em->getConnection()->connect();

        $updatedIncome = $this->em->getRepository(Income::class)->find($income->getId());
        self::assertSame(Income::STATUS_CANCELLED, $updatedIncome->getStatus());

        // Verify we have:
        // 1. Original Pending (reversed, but still there unless we assert explicit reversal logic which just creates a correction tx)
        // 2. Correction/Reversal
        // 3. New Void Transaction

        $voidTx = $this->em->getRepository(Transaction::class)->findOneBy([
            'relatedEntityId' => $income->getId(),
            'relatedEntityType' => 'Income',
            'status' => Transaction::STATUS_VOID
        ]);
        self::assertNotNull($voidTx, 'Void Transaction should exist');

        // 6. Verify UI Watermark presence by revisiting edit
        $crawler = $this->client->request('GET', '/income/edit/' . $income->getId());
        // Verify text content snippet from the alert
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
