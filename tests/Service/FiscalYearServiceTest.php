<?php

namespace App\Tests\Service;

use App\Entity\Asset;
use App\Entity\Transaction;
use App\Entity\TransactionArchive;
use App\Service\FiscalYearService;
use App\Service\LedgerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FiscalYearServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private FiscalYearService $fiscalYearService;
    private LedgerService $ledgerService;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->fiscalYearService = $container->get(FiscalYearService::class);
        $this->ledgerService = $container->get(LedgerService::class);
    }

    protected function tearDown(): void
    {
        // Cleanup data manually
        $this->entityManager->createQuery('DELETE FROM App\Entity\TransactionArchive')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Transaction')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Asset')->execute();

        parent::tearDown();
    }

    public function testCloseFiscalYearSuccess(): void
    {
        // 1. Create Asset
        $asset = new Asset();
        $asset->setName('Test Asset');
        $asset->setType('Ship');
        $asset->setCredits('0.00');
        $this->entityManager->persist($asset);
        $this->entityManager->flush();

        // 2. Create Transactions for Year 1105
        $t1 = $this->ledgerService->deposit($asset, '1000.00', 'Income 1', 10, 1105, null, null, Transaction::STATUS_POSTED);
        $t2 = $this->ledgerService->withdraw($asset, '200.00', 'Cost 1', 20, 1105, null, null, Transaction::STATUS_POSTED);

        // 3. Create Transaction for Year 1106 (Should not be touched)
        $t3 = $this->ledgerService->deposit($asset, '500.00', 'Income 2', 5, 1106, null, null, Transaction::STATUS_POSTED);

        $this->entityManager->flush();

        // Initial Checks
        $this->assertEquals('1300.00', $asset->getCredits(), 'Initial Balance check');
        // 1000 - 200 + 500 = 1300

        $t1Id = $t1->getId();
        $t2Id = $t2->getId();
        $t3Id = $t3->getId();

        // 4. Close Year 1105
        $this->fiscalYearService->closeFiscalYear($asset, 1105);

        // 5. Verification

        // A. Original 1105 transactions should be gone
        $repoTx = $this->entityManager->getRepository(Transaction::class);
        $this->assertNull($repoTx->find($t1Id), 'Transaction 1 should be gone');
        $this->assertNull($repoTx->find($t2Id), 'Transaction 2 should be gone');
        $this->assertNotNull($repoTx->find($t3Id), 'Transaction 3 (Year 1106) should remain');

        // B. Archive should have correct count and values
        $repoArch = $this->entityManager->getRepository(TransactionArchive::class);
        $archives = $repoArch->findBy(['assetId' => $asset->getId(), 'sessionYear' => 1105]);
        $this->assertCount(2, $archives, 'Should have 2 archived transactions');

        // C. Snapshot should differ from Sum.
        // Sum 1105 = 1000 - 200 = 800.
        // Snapshot should be created for Year 1106 with amount 800.
        $snapshot = $repoTx->findOneBy([
            'asset' => $asset,
            'relatedEntityType' => 'SNAPSHOT',
            'relatedEntityId' => 1105
        ]);

        $this->assertNotNull($snapshot, 'Snapshot transaction should exist');
        $this->assertEquals('800.00', $snapshot->getAmount(), 'Snapshot amount correct');
        $this->assertEquals(1106, $snapshot->getSessionYear(), 'Snapshot year correct');

        // D. Asset Balance Check
        // Balance logic: Sum of remaining transactions.
        // Remaining: T3 (500) + Snapshot (800) = 1300.
        // Asset entity 'credits' field was NOT changed by closeFiscalYear.
        // It is 1300.
        $this->entityManager->refresh($asset); // Refresh to be sure
        // Normalize for comparison (SQLite may drop trailing zeros)
        $this->assertEquals('1300.00', number_format((float)$asset->getCredits(), 2, '.', ''), 'Asset balance should remain consistent');
    }

    public function testCloseFiscalYearWithPendingTransactions(): void
    {
        $asset = new Asset();
        $asset->setName('Test Asset Pending');
        $asset->setType('Ship');
        $this->entityManager->persist($asset);

        $t = $this->ledgerService->deposit($asset, '100.00', 'Pending T', 10, 1105, null, null, Transaction::STATUS_PENDING);
        // Force status to PENDING as LedgerService defaults to POSTED if no campaign
        $t->setStatus(Transaction::STATUS_PENDING);
        $this->entityManager->flush();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('pending or void transactions');

        $this->fiscalYearService->closeFiscalYear($asset, 1105);
    }
}
