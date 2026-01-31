<?php

namespace App\Command;

use App\Entity\Asset;
use App\Entity\Campaign;
use App\Entity\User;
use App\Service\FinancialAccountManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:verify:consistency',
    description: 'Verifies Asset-FinancialAccount campaign consistency logic',
)]
class VerifyConsistencyCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private FinancialAccountManager $manager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Consistency Verification Protocol');

        // 1. Get User
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'wolfmaniii@gmail.com']);
        if (!$user) {
            $io->error('User wolfmaniii@gmail.com not found. Run fixtures first.');
            return Command::FAILURE;
        }

        // 2. Create Campaign
        $campaign = new Campaign();
        $campaign->setTitle("Test Campaign " . uniqid());
        $campaign->setUser($user);
        $this->em->persist($campaign);

        // 3. Create Asset in Campaign
        $asset = new Asset();
        $asset->setName("Test Ship (Coherence)");
        $asset->setCategory('ship');
        $asset->setCampaign($campaign);
        $asset->setUser($user);
        $this->em->persist($asset);

        $this->em->flush();

        $io->section('1. Setup');
        $io->text("Created Asset '{$asset->getName()}' in Campaign '{$campaign->getTitle()}'");

        // 4. Create Financial Account (without campaign arg)
        $account = $this->manager->createForAsset($asset, $user, '1000', null, 'Test Bank');
        $this->em->flush();

        $io->text("Created Financial Service for Asset (Implicit Link)");

        // 5. Verify Initial State
        $io->section('2. Initial State Verification');
        $accCampaign = $account->getCampaign();

        if ($accCampaign === $campaign) {
            $io->success("SUCCESS: Financial Account implicitly derived Campaign '{$campaign->getTitle()}' from Asset.");
        } else {
            $io->error("FAIL: Financial Account Campaign mismatch.");
            return Command::FAILURE;
        }

        // 6. Move Asset
        $io->section('3. Movement Simulation');
        $newCampaign = new Campaign();
        $newCampaign->setTitle("New Sector " . uniqid());
        $newCampaign->setUser($user);
        $this->em->persist($newCampaign);

        $asset->setCampaign($newCampaign);
        $this->em->flush();

        $io->text("Moved Asset to '{$newCampaign->getTitle()}'");

        // 7. Verify Sync
        $io->section('4. Sync Verification');
        $accCampaignV2 = $account->getCampaign();

        if ($accCampaignV2 === $newCampaign) {
            $io->success("SUCCESS: Financial Account AUTOMATICALLY followed Asset to '{$newCampaign->getTitle()}'. Zero latency.");
        } else {
            $io->error("FAIL: Sync failed. Account remained in old campaign.");
            return Command::FAILURE;
        }

        $io->success('SYSTEM INTEGRITY: 100%');
        return Command::SUCCESS;
    }
}
