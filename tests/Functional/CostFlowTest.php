<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Asset;
use App\Entity\Campaign;
use App\Entity\Cost;
use App\Entity\CostCategory;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CostFlowTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $dbPath = dirname(__DIR__, 2) . '/var/test_cost_flow.db';
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

    public function testCostCreationCreatesTransaction(): void
    {
        // 1. Setup Utente, Asset, Campagna
        $user = $this->createUser('quartermaster@cost.test');
        $this->login($user);

        $campaign = new Campaign();
        $campaign->setTitle('Test Campaign');
        $campaign->setSessionDay(100);
        $campaign->setSessionYear(1105);
        $this->em->persist($campaign);

        $asset = new Asset();
        $asset->setUser($user);
        $asset->setName('ISS Expensive');
        $asset->setType('Trader');
        $asset->setClass('A-1');
        $asset->setPrice('1000000.00');
        $asset->setCampaign($campaign);
        $this->em->persist($asset);

        $fa = new \App\Entity\FinancialAccount();
        $fa->setAsset($asset);
        $fa->setUser($user);
        $this->em->persist($fa);

        $category = new CostCategory();
        $category->setCode('FUEL');
        $category->setDescription('Fuel and Supplies');
        $this->em->persist($category);

        $localLaw = new \App\Entity\LocalLaw();
        $localLaw->setCode('IMP_FULL');
        $localLaw->setDescription('Imperial Law Full');
        $this->em->persist($localLaw);

        $role = new \App\Entity\CompanyRole();
        $role->setCode('FUEL_SUPPLIER');
        $role->setDescription('Fuel Supplier');
        $this->em->persist($role);

        $vendor = new \App\Entity\Company();
        $vendor->setName('H-Fuel Inc.');
        $vendor->setCode('HFUEL');
        $vendor->setUser($user);
        $vendor->setCompanyRole($role);
        $this->em->persist($vendor);

        $this->em->flush();

        // 2. Crea costo via UI (POST manuale per gestire la collezione dinamica)
        $crawler = $this->client->request('GET', '/cost/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Confirm Entry // Commit to Log')->form();
        $token = $form['cost[_token]']->getValue();

        $this->client->request('POST', '/cost/new', [
            'cost' => [
                'title' => 'Hydrogen Refuel',
                'paymentDate' => ['day' => 105, 'year' => 1105],
                'financialAccount' => $fa->getId(),
                'costCategory' => $category->getId(),
                'detailItems' => [
                    0 => [
                        'description' => 'Liquid Hydrogen',
                        'quantity' => 5,
                        'cost' => 100.00,
                    ]
                ],
                '_token' => $token,
                'note' => '',
                'note' => '',
                'company' => $vendor->getId(),
                'localLaw' => $localLaw->getId(),
            ]
        ]);
        self::assertResponseRedirects('/cost/index');
        $this->client->followRedirect();

        // 3. Verifica costo creato e transazione esistente
        $cost = $this->em->getRepository(Cost::class)->findOneBy(['title' => 'Hydrogen Refuel']);
        self::assertNotNull($cost);
        self::assertEquals(500.00, (float) $cost->getAmount());

        $transaction = $this->em->getRepository(Transaction::class)->findOneBy([
            'relatedEntityId' => $cost->getId(),
            'relatedEntityType' => 'Cost'
        ]);
        self::assertNotNull($transaction, 'Transaction should exist for Cost');
        self::assertEquals(-500.00, (float) $transaction->getAmount());
        self::assertSame(105, $transaction->getSessionDay());
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
