<?php

namespace App\Dto;

class ShipDetailsData
{
    public ?string $techLevel = null;
    public ?float $totalCost = null;

    public ShipDetailItem $hull;
    public MDriveDetailItem $mDrive;
    public JDriveDetailItem $jDrive;
    public ShipDetailItem $powerPlant;
    public ShipDetailItem $fuel;
    public ShipDetailItem $bridge;
    public ShipDetailItem $computer;
    public ShipDetailItem $sensors;
    public ShipDetailItem $commonAreas;
    public ShipDetailItem $cargo;

    /** @var ShipDetailItem[] */
    public array $weapons = [];
    /** @var ShipDetailItem[] */
    public array $craft = [];
    /** @var ShipDetailItem[] */
    public array $systems = [];
    /** @var ShipDetailItem[] */
    public array $staterooms = [];
    /** @var ShipDetailItem[] */
    public array $software = [];

    public function __construct()
    {
        $this->hull = new ShipDetailItem();
        $this->mDrive = new MDriveDetailItem();
        $this->jDrive = new JDriveDetailItem();
        $this->powerPlant = new ShipDetailItem();
        $this->fuel = new ShipDetailItem();
        $this->bridge = new ShipDetailItem();
        $this->computer = new ShipDetailItem();
        $this->sensors = new ShipDetailItem();
        $this->commonAreas = new ShipDetailItem();
        $this->cargo = new ShipDetailItem();

        // Seed collections with one empty item so the form shows fields by default.
        $this->weapons = [new ShipDetailItem()];
        $this->craft = [new ShipDetailItem()];
        $this->systems = [new ShipDetailItem()];
        $this->staterooms = [new ShipDetailItem()];
        $this->software = [new ShipDetailItem()];
    }

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->techLevel = $data['techLevel'] ?? null;
        $dto->totalCost = isset($data['totalCost']) ? (float) $data['totalCost'] : null;

        $dto->hull = ShipDetailItem::fromArray($data['hull'] ?? []);
        $dto->mDrive = MDriveDetailItem::fromArray($data['mDrive'] ?? []);
        $dto->jDrive = JDriveDetailItem::fromArray($data['jDrive'] ?? []);
        $dto->powerPlant = ShipDetailItem::fromArray($data['powerPlant'] ?? []);
        $dto->fuel = ShipDetailItem::fromArray($data['fuel'] ?? []);
        $dto->bridge = ShipDetailItem::fromArray($data['bridge'] ?? []);
        $dto->computer = ShipDetailItem::fromArray($data['computer'] ?? []);
        $dto->sensors = ShipDetailItem::fromArray($data['sensors'] ?? []);
        $dto->commonAreas = ShipDetailItem::fromArray($data['commonAreas'] ?? []);
        $dto->cargo = ShipDetailItem::fromArray($data['cargo'] ?? []);

        $dto->weapons = array_map(fn ($item) => ShipDetailItem::fromArray($item), $data['weapons'] ?? []);
        $dto->craft = array_map(fn ($item) => ShipDetailItem::fromArray($item), $data['craft'] ?? []);
        $dto->systems = array_map(fn ($item) => ShipDetailItem::fromArray($item), $data['systems'] ?? []);
        $dto->staterooms = array_map(fn ($item) => ShipDetailItem::fromArray($item), $data['staterooms'] ?? []);
        $dto->software = array_map(fn ($item) => ShipDetailItem::fromArray($item), $data['software'] ?? []);

        // Ensure at least one row per collection so the form renders inputs.
        if (count($dto->weapons) === 0) {
            $dto->weapons[] = new ShipDetailItem();
        }
        if (count($dto->craft) === 0) {
            $dto->craft[] = new ShipDetailItem();
        }
        if (count($dto->systems) === 0) {
            $dto->systems[] = new ShipDetailItem();
        }
        if (count($dto->staterooms) === 0) {
            $dto->staterooms[] = new ShipDetailItem();
        }
        if (count($dto->software) === 0) {
            $dto->software[] = new ShipDetailItem();
        }

        return $dto;
    }

    public function toArray(): array
    {
        $mapCollection = function (array $items): array {
            $filtered = array_filter($items, fn ($i) => $i instanceof ShipDetailItem);

            return array_values(array_map(
                fn (ShipDetailItem $i) => $i->toArray(),
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
