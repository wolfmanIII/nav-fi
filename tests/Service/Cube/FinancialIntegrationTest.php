<?php

namespace App\Tests\Service\Cube;

use App\Dto\Cube\CubeOpportunityData;
use App\Entity\Asset;
use App\Entity\Cost;
use App\Entity\CostCategory;
use App\Entity\Income;
use App\Entity\IncomeCategory;
use App\Service\Cube\OpportunityConverter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class FinancialIntegrationTest extends KernelTestCase
{
    private ?EntityManagerInterface $em;
    private ?OpportunityConverter $converter;
    private ?Asset $asset;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->converter = $container->get(OpportunityConverter::class);
        $this->em = $container->get(EntityManagerInterface::class);

        $this->em->beginTransaction();

        // Setup Base Entities
        $user = new \App\Entity\User();
        $user->setEmail('test_fi_' . uniqid() . '@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);
        $this->em->persist($user);

        $campaign = new \App\Entity\Campaign();
        $campaign->setTitle('Test FI Campaign');
        $campaign->setCode(Uuid::v4());
        $campaign->setUser($user);
        $this->em->persist($campaign);

        $this->asset = new Asset();
        $this->asset->setName('Test Ship');
        $this->asset->setCode(Uuid::v4());
        $this->asset->setCategory('ship');
        $this->asset->setCampaign($campaign);
        $this->asset->setUser($user);
        $this->asset->setCredits('1000000.00');
        $this->em->persist($this->asset);

        // Ensure Categories Exist
        $this->ensureCategories();

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $this->em->rollback();
        $this->em->close();
        $this->em = null; // avoid memory leaks
        parent::tearDown();
    }

    public function testConvertContractToIncome(): void
    {
        $dto = new CubeOpportunityData(
            signature: 'SIG123',
            type: 'CONTRACT',
            summary: '[Routine] Escort Mission',
            distance: 0,
            amount: 5000.0,
            details: [
                'mission_type' => 'Escort',
                'origin' => 'Test Location',
                'patron' => 'Test Patron',
                'difficulty' => 'Routine',
                'tier' => 'hazardous' // Added to test deep details logic
            ]
        );

        $result = $this->converter->convert($dto, $this->asset);

        $this->assertInstanceOf(Income::class, $result);
        $this->assertEquals('5000', $result->getAmount());
        $this->assertEquals('[Routine] Escort Mission', $result->getTitle());
        $this->assertEquals(Income::STATUS_SIGNED, $result->getStatus());
        $this->assertEquals('Test Patron', $result->getPatronAlias());
        $this->assertInstanceOf(\App\Entity\IncomeContractDetails::class, $result->getContractDetails());
        $this->assertEquals('Escort', $result->getContractDetails()->getJobType());
    }

    public function testConvertTradeToCost(): void
    {
        $dto = new CubeOpportunityData(
            signature: 'SIG789',
            type: 'TRADE',
            summary: 'Buy Advanced Components',
            distance: 0,
            amount: 20000.0,
            details: [
                'goods' => 'Advanced Components',
                'tons' => 10,
                'start_day' => 110,
                'start_year' => 1105,
                'destination' => 'Core'
            ]
        );

        $result = $this->converter->convert($dto, $this->asset);

        $this->assertInstanceOf(Cost::class, $result);
        $this->assertEquals('20000', $result->getAmount());
        $this->assertStringContainsString('Advanced Components', $result->getTitle());
        $this->assertEquals(110, $result->getPaymentDay());
        $this->assertEquals(1105, $result->getPaymentYear());

        $details = $result->getDetailItems();
        $this->assertIsArray($details);
        $this->assertEquals(10, $details['quantity']);
    }

    private function ensureCategories(): void
    {
        // Check and create Income Categories
        foreach (['CONTRACT', 'FREIGHT', 'PASSENGER', 'MAIL', 'TRADE'] as $code) {
            $repo = $this->em->getRepository(IncomeCategory::class);
            if (!$repo->findOneBy(['code' => $code])) {
                $cat = new IncomeCategory();
                $cat->setCode($code);
                $cat->setDescription("Test $code");
                $this->em->persist($cat);
            }
        }

        // Check and create Cost Categories
        foreach (['TRADE'] as $code) {
            $repo = $this->em->getRepository(CostCategory::class);
            if (!$repo->findOneBy(['code' => $code])) {
                $cat = new CostCategory();
                $cat->setCode($code);
                $cat->setDescription("Test $code");
                $this->em->persist($cat);
            }
        }
    }
}
