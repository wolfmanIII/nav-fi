<?php

declare(strict_types=1);

namespace App\Tests\Form\Type;

use App\Form\Type\ImperialDateType;
use App\Model\ImperialDate;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Range;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[CoversClass(ImperialDateType::class)]
#[AllowMockObjectsWithoutExpectations]
class ImperialDateTypeTest extends TypeTestCase
{
    private const MIN_YEAR = 1100;
    private const MAX_YEAR = 1200;
    private const MIN_DAY = 1;
    private const MAX_DAY = 365;

    protected function getExtensions(): array
    {
        // Serve per testare che il type funzioni con le dipendenze iniettate
        $type = new ImperialDateType(
            self::MIN_YEAR,
            self::MAX_YEAR,
            self::MIN_DAY,
            self::MAX_DAY
        );

        return [
            // Registriamo il type come pre-configurato
            new \Symfony\Component\Form\PreloadedExtension([$type], []),
            // Necessario per abilitare l'opzione 'constraints' nei campi
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'display' => '050/1105', // Ignorato dal form (mapped: false)
            'year' => '1105',
            'day' => '50',
        ];

        $form = $this->factory->create(ImperialDateType::class);

        // $model verrà popolato dal form
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $data = $form->getData();
        $this->assertInstanceOf(ImperialDate::class, $data);
        $this->assertSame(1105, $data->getYear());
        $this->assertSame(50, $data->getDay());

        $view = $form->createView();
        $yearAttr = $view->children['year']->vars['attr'];

        // Verifica attributi data per il frontend
        $this->assertSame(self::MIN_YEAR, $yearAttr['data-min-year']);
        $this->assertSame(self::MAX_YEAR, $yearAttr['data-max-year']);
        $this->assertSame(self::MIN_DAY, $yearAttr['data-min-day']);
        $this->assertSame(self::MAX_DAY, $yearAttr['data-max-day']);
    }

    public function testDayRangeConstraintIsApplied(): void
    {
        $form = $this->factory->create(ImperialDateType::class);
        $dayConfig = $form->get('day')->getConfig();
        $constraints = $dayConfig->getOption('constraints');

        $rangeConstraint = null;
        foreach ($constraints as $constraint) {
            if ($constraint instanceof Range) {
                $rangeConstraint = $constraint;
                break;
            }
        }

        $this->assertNotNull($rangeConstraint, 'Range constraint not found on "day" field.');
        $this->assertSame(self::MIN_DAY, $rangeConstraint->min);
        $this->assertSame(self::MAX_DAY, $rangeConstraint->max);
    }
}
