<?php

namespace App\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;

class ShipDetailsData
{
    #[Assert\Valid]
    public HullData $hull;

    #[Assert\Valid]
    public DriveData $jDrive;

    #[Assert\Valid]
    public DriveData $mDrive;

    #[Assert\Valid]
    public DriveData $powerPlant;

    #[Assert\Valid]
    public FuelData $fuel;

    #[Assert\Valid]
    public GenericComponentData $bridge;

    #[Assert\Valid]
    public GenericComponentData $computer;

    #[Assert\Valid]
    public GenericComponentData $sensors;

    #[Assert\Valid]
    public GenericComponentData $cargo;

    /** @var \App\Dto\AssetDetailItem[] */
    public array $staterooms = [];

    /** @var \App\Dto\AssetDetailItem[] */
    public array $commonAreas = [];

    /** @var \App\Dto\AssetDetailItem[] */
    public array $weapons = [];

    /** @var \App\Dto\AssetDetailItem[] */
    public array $systems = [];

    /** @var \App\Dto\AssetDetailItem[] */
    public array $software = [];

    /** @var \App\Dto\AssetDetailItem[] */
    public array $craft = [];

    public ?int $techLevel = null;
    public ?float $totalCost = null;

    public function __construct()
    {
        $this->hull = new HullData();
        $this->jDrive = DriveData::create('jump');
        $this->mDrive = DriveData::create('rating');
        $this->powerPlant = DriveData::create('output');
        $this->fuel = new FuelData();
        $this->bridge = new GenericComponentData();
        $this->computer = new GenericComponentData();
        $this->sensors = new GenericComponentData();
        $this->cargo = new GenericComponentData();
    }

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->hull = HullData::fromArray($data['hull'] ?? []);
        $dto->jDrive = DriveData::fromArray($data['jDrive'] ?? [], 'jump');
        $dto->mDrive = DriveData::fromArray($data['mDrive'] ?? [], 'rating');
        $dto->powerPlant = DriveData::fromArray($data['powerPlant'] ?? [], 'output');
        $dto->fuel = FuelData::fromArray($data['fuel'] ?? []);
        
        $dto->bridge = GenericComponentData::fromArray($data['bridge'] ?? []);
        $dto->computer = GenericComponentData::fromArray($data['computer'] ?? []);
        $dto->sensors = GenericComponentData::fromArray($data['sensors'] ?? []);
        $dto->cargo = GenericComponentData::fromArray($data['cargo'] ?? []);

        // Helper per le collezioni
        $hydrateCollection = fn(array $items) => array_map(
            fn($item) => \App\Dto\AssetDetailItem::fromArray($item),
            $items
        );

        $dto->staterooms = $hydrateCollection($data['staterooms'] ?? []);
        $dto->commonAreas = $hydrateCollection($data['commonAreas'] ?? []);
        $dto->weapons = $hydrateCollection($data['weapons'] ?? []);
        $dto->systems = $hydrateCollection($data['systems'] ?? []);
        $dto->software = $hydrateCollection($data['software'] ?? []);
        $dto->craft = $hydrateCollection($data['craft'] ?? []);

        $dto->techLevel = isset($data['techLevel']) ? (int)$data['techLevel'] : null;
        $dto->totalCost = isset($data['totalCost']) ? (float)$data['totalCost'] : null;
        
        return $dto;
    }

    public function toArray(): array
    {
        $serializeCollection = fn(array $items) => array_map(
            fn($item) => $item instanceof \App\Dto\AssetDetailItem ? $item->toArray() : $item,
            $items
        );

        return [
            'hull' => $this->hull->toArray(),
            'jDrive' => $this->jDrive->toArray(),
            'mDrive' => $this->mDrive->toArray(),
            'powerPlant' => $this->powerPlant->toArray(),
            'fuel' => $this->fuel->toArray(),
            'bridge' => $this->bridge->toArray(),
            'computer' => $this->computer->toArray(),
            'sensors' => $this->sensors->toArray(),
            'cargo' => $this->cargo->toArray(),
            'staterooms' => $serializeCollection($this->staterooms),
            'commonAreas' => $serializeCollection($this->commonAreas),
            'weapons' => $serializeCollection($this->weapons),
            'systems' => $serializeCollection($this->systems),
            'software' => $serializeCollection($this->software),
            'craft' => $serializeCollection($this->craft),
            'techLevel' => $this->techLevel,
            'totalCost' => $this->totalCost,
        ];
    }
}
