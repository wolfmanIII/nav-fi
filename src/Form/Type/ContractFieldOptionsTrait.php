<?php

namespace App\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

trait ContractFieldOptionsTrait
{
    private function addIfEnabled(
        FormBuilderInterface $builder,
        array $options,
        string $name,
        string $type,
        array $config = []
    ): void {
        if (!$this->isEnabled($options, $name)) {
            return;
        }

        if (isset($config['attr'])) {
            $config['attr'] = $this->withPlaceholder($options, $name, $config['attr']);
        } elseif ($placeholder = $this->getPlaceholder($options, $name)) {
            $config['attr'] = ['placeholder' => $placeholder];
        }

        $builder->add($name, $type, $config);
    }

    private function isEnabled(array $options, string $field): bool
    {
        $enabled = $options['enabled_fields'] ?? null;
        if ($enabled === null) {
            return true;
        }

        return in_array($field, $enabled, true);
    }

    /**
     * @param array<string, mixed> $attr
     *
     * @return array<string, mixed>
     */
    private function withPlaceholder(array $options, string $field, array $attr): array
    {
        $placeholder = $this->getPlaceholder($options, $field);
        if ($placeholder === null || $placeholder === '') {
            return $attr;
        }

        return array_merge($attr, ['placeholder' => $placeholder]);
    }

    private function getPlaceholder(array $options, string $field): ?string
    {
        $placeholders = $options['field_placeholders'] ?? [];

        return $placeholders[$field] ?? null;
    }
}
