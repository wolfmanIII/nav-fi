<?php

namespace App\Form;

use App\Entity\Asset;
use App\Entity\BrokerOpportunity;
use App\Entity\Company;
use App\Entity\CompanyRole;
use App\Entity\LocalLaw;
use App\Form\Type\ImperialDateType;
use App\Model\ImperialDate;
use App\Form\Config\DayYearLimits;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContractAcceptanceType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var BrokerOpportunity $opportunity */
        $opportunity = $options['opportunity'];
        $existingPatron = $options['existing_patron'];
        $oppData = $opportunity->getData();
        $details = $oppData['details'] ?? [];
        $type = $oppData['type'] ?? 'UNKNOWN';

        // Defaults from Opportunity
        $startDay = $details['start_day'] ?? 1;
        $startYear = $details['start_year'] ?? 1105;
        $startDate = new ImperialDate((int)$startYear, (int)$startDay);

        $deadlineDate = null;
        if ($type === 'CONTRACT') {
            $deadlineDate = new ImperialDate((int)$startYear, null); // Default to same year, no day
        }

        $builder
            ->add('asset', EntityType::class, [
                'class' => Asset::class,
                'label' => 'Target Asset',
                'placeholder' => '// ASSET',
                'required' => true,
                'choice_label' => fn(Asset $asset) => sprintf('%s - %s(%s) [CODE: %s]', $asset->getName(), $asset->getType(), $asset->getClass(), substr($asset->getFinancialAccount()?->getCode() ?? 'N/A', 0, 8)),
                'query_builder' => function (EntityRepository $er) use ($opportunity) {
                    return $er->createQueryBuilder('a')
                        ->where('a.campaign = :campaign')
                        ->setParameter('campaign', $opportunity->getSession()->getCampaign())
                        ->orderBy('a.name', 'ASC');
                },
                'attr' => ['class' => 'select select-bordered bg-slate-950 border-slate-700 text-white w-full'],
                'label_attr' => ['class' => 'label-text text-slate-400 text-xs uppercase font-bold'],
            ])
            ->add('localLaw', EntityType::class, [
                'class' => LocalLaw::class,
                'label' => 'Local Law (Jurisdiction)',
                'placeholder' => '// LOCAL LAW',
                'required' => true,
                'choice_label' => fn(LocalLaw $law) => $law->getShortDescription() ?? $law->getCode(),
                'attr' => ['class' => 'select select-bordered bg-slate-950 border-slate-700 text-white w-full'],
                'label_attr' => ['class' => 'label-text text-slate-400 text-xs uppercase font-bold'],
            ]);

        // Date Logic
        if ($type !== 'TRADE') {
            $dateLabel = match ($type) {
                'FREIGHT' => 'Pickup Date',
                'PASSENGERS' => 'Departure Date',
                'MAIL' => 'Dispatch Date',
                'CONTRACT' => 'Start Date',
                default => 'Date',
            };

            $builder->add('startDate', ImperialDateType::class, [
                'label' => $dateLabel,
                'mapped' => false,
                'data' => $startDate,
                'min_year' => $this->limits->getYearMin(),
                'max_year' => $this->limits->getYearMax(),
                'required' => true,
            ]);
        }

        if ($type === 'CONTRACT') {
            $builder->add('deadlineDate', ImperialDateType::class, [
                'label' => 'Optional: Contract Deadline',
                'mapped' => false,
                'data' => $deadlineDate,
                'min_year' => $this->limits->getYearMin(),
                'max_year' => $this->limits->getYearMax(),
                'required' => false,
            ]);
        }

        // Patron Logic
        if (isset($details['patron'])) {
            if ($existingPatron) {
                $builder->add('patronCompany', HiddenType::class, [
                    'mapped' => false,
                    'data' => $existingPatron->getId(),
                ]);
            } else {
                $builder->add('patronRole', EntityType::class, [
                    'class' => CompanyRole::class,
                    'mapped' => false,
                    'label' => 'Register as Company? (Select Role)',
                    'placeholder' => '// ROLE',
                    'required' => false, // Handled by validator manually if needed
                    'choice_label' => fn(CompanyRole $role) => $role->getShortDescription() ?? $role->getCode(),
                    'attr' => ['class' => 'select select-bordered select-sm bg-slate-900 border-slate-800 text-white w-full'],
                ]);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'opportunity' => null,
            'existing_patron' => null,
        ]);
        $resolver->setAllowedTypes('opportunity', [BrokerOpportunity::class]);
        $resolver->setAllowedTypes('existing_patron', [Company::class, 'null']);
    }
}
