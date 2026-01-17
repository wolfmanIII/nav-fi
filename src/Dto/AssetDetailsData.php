<?php

namespace App\Dto;

class AssetDetailsData
{
    public ?string $techLevel = null;
    public ?float $totalCost = null;

    public AssetDetailItem $hull;
    public MDriveDetailItem $mDrive;
    public JDriveDetailItem $jDrive;
    public PowerPlantDetailItem $powerPlant;
    public AssetDetailItem $fuel;
    public AssetDetailItem $bridge;
    public AssetDetailItem $computer;
    public AssetDetailItem $sensors;
    public AssetDetailItem $commonAreas;
    public AssetDetailItem $cargo;

    /** @var AssetDetailItem[] */
    public array $weapons = [];
    /** @var AssetDetailItem[] */
    public array $craft = [];
    /** @var AssetDetailItem[] */
    public array $systems = [];
    /** @var AssetDetailItem[] */
    public array $staterooms = [];
    /** @var AssetDetailItem[] */
    public array $software = [];

    public function __construct()
    {
        $this->hull = new AssetDetailItem();
        $this->mDrive = new MDriveDetailItem();
        $this->jDrive = new JDriveDetailItem();
        $this->powerPlant = new PowerPlantDetailItem();
        $this->fuel = new AssetDetailItem();
        $this->bridge = new AssetDetailItem();
        $this->computer = new AssetDetailItem();
        $this->sensors = new AssetDetailItem();
        $this->commonAreas = new AssetDetailItem();
        $this->cargo = new AssetDetailItem();

        // Seed collections with one empty item so the form shows fields by default.
        $this->weapons = [new AssetDetailItem()];
        $this->craft = [new AssetDetailItem()];
        $this->systems = [new AssetDetailItem()];
        $this->staterooms = [new AssetDetailItem()];
        $this->software = [new AssetDetailItem()];
    }

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->techLevel = $data['techLevel'] ?? null;
        $dto->totalCost = isset($data['totalCost']) ? (float) $data['totalCost'] : null;

        $dto->hull = AssetDetailItem::fromArray($data['hull'] ?? []);
        $dto->mDrive = MDriveDetailItem::fromArray($data['mDrive'] ?? []);
        $dto->jDrive = JDriveDetailItem::fromArray($data['jDrive'] ?? []);
        $dto->powerPlant = PowerPlantDetailItem::fromArray($data['powerPlant'] ?? []);
        $dto->fuel = AssetDetailItem::fromArray($data['fuel'] ?? []);
        $dto->bridge = AssetDetailItem::fromArray($data['bridge'] ?? []);
        $dto->computer = AssetDetailItem::fromArray($data['computer'] ?? []);
        $dto->sensors = AssetDetailItem::fromArray($data['sensors'] ?? []);
        $dto->commonAreas = AssetDetailItem::fromArray($data['commonAreas'] ?? []);
        $dto->cargo = AssetDetailItem::fromArray($data['cargo'] ?? []);

        $dto->weapons = array_map(fn($item) => AssetDetailItem::fromArray($item), $data['weapons'] ?? []);
        $dto->craft = array_map(fn($item) => AssetDetailItem::fromArray($item), $data['craft'] ?? []);
        $dto->systems = array_map(fn($item) => AssetDetailItem::fromArray($item), $data['systems'] ?? []);
        $dto->staterooms = array_map(fn($item) => AssetDetailItem::fromArray($item), $data['staterooms'] ?? []);
        $dto->software = array_map(fn($item) => AssetDetailItem::fromArray($item), $data['software'] ?? []);

        // Ensure at least one row per collection so the form renders inputs.
        if (count($dto->weapons) === 0) {
            $dto->weapons[] = new AssetDetailItem();
        }
        if (count($dto->craft) === 0) {
            $dto->craft[] = new AssetDetailItem();
        }
        if (count($dto->systems) === 0) {
            $dto->systems[] = new AssetDetailItem();
        }
        if (count($dto->staterooms) === 0) {
            $dto->staterooms[] = new AssetDetailItem();
        }
        if (count($dto->software) === 0) {
            $dto->software[] = new AssetDetailItem();
        }

        return $dto;
    }

    public function toArray(): array
    {
        $mapCollection = function (array $items): array {
            $filtered = array_filter($items, fn($i) => $i instanceof AssetDetailItem);

            return array_values(array_map(
                fn(AssetDetailItem $i) => $i->toArray(),
                $filtered
            ));
        };

        return [
            'techLevel' => $this->techLevel,
            'totalCost' => $this->totalCost,
            'hull' => $this->hull->toArray(),
            'mDrive' => $this->mDrive->toArray(),
            'jDrive' => $this->jDrive->toArray(),
            'powerPlant' => $this->powerPlant->toArray(),
            'fuel' => $this->fuel->toArray(),
            'bridge' => $this->bridge->toArray(),
            'computer' => $this->computer->toArray(),
            'sensors' => $this->sensors->toArray(),
            'commonAreas' => $this->commonAreas->toArray(),
            'cargo' => $this->cargo->toArray(),
            'weapons' => $mapCollection($this->weapons),
            'craft' => $mapCollection($this->craft),
            'systems' => $mapCollection($this->systems),
            'staterooms' => $mapCollection($this->staterooms),
            'software' => $mapCollection($this->software),
        ];
    }
}
