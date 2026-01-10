<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Crew;
use App\Entity\Ship;
use App\Entity\ShipRole;
use App\Entity\User;
use App\Repository\CrewRepository;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class CrewPersistenceTest extends TestCase
{
    private ?EntityManagerInterface $em = null;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [dirname(__DIR__) . '/src/Entity'],
            isDevMode: true,
        );

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $config);

        if (!Type::hasType('uuid')) {
            Type::addType('uuid', \Doctrine\DBAL\Types\StringType::class);
        }
        if (!Type::hasType('uuid_binary')) {
            Type::addType('uuid_binary', \Doctrine\DBAL\Types\StringType::class);
        }

        $platform = $connection->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('uuid', 'string');
        $platform->registerDoctrineTypeMapping('uuid_binary', 'string');

        $this->em = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        if ($this->em !== null) {
            $this->em->close();
        }
        $this->em = null;
        parent::tearDown();
    }

    public function testCrewDefaultsAndCode(): void
    {
        $user = $this->makeUser('crew-defaults@log.test');
        $crew = $this->makeCrew($user, 'Talia', 'Rook');

        $this->em->persist($user);
        $this->em->persist($crew);
        $this->em->flush();

        self::assertSame('Active', $crew->getStatus());
        self::assertNotNull($crew->getCode());

        $uuid = Uuid::fromString((string) $crew->getCode());
        self::assertInstanceOf(\Symfony\Component\Uid\UuidV7::class, $uuid);
    }

    public function testFindUnassignedExcludesMissingAndDeceased(): void
    {
        $user = $this->makeUser('crew-filter@log.test');
        $active = $this->makeCrew($user, 'Ari', 'Voss', 'Active');
        $missing = $this->makeCrew($user, 'Mia', 'Lost', 'Missing (MIA)');
        $deceased = $this->makeCrew($user, 'Vera', 'Void', 'Deceased');

        $this->em->persist($user);
        $this->em->persist($active);
        $this->em->persist($missing);
        $this->em->persist($deceased);
        $this->em->flush();

        $repo = $this->makeCrewRepository();

        $result = $repo->findUnassignedForShip($user, [], 1, 10, true);
        self::assertCount(1, $result['items']);
        self::assertSame('Active', $result['items'][0]->getStatus());
    }

    public function testFindUnassignedHonorsNeedCaptainFlag(): void
    {
        $user = $this->makeUser('crew-captain@log.test');
        $captainRole = $this->makeRole('CAP', 'Captain');
        $crew = $this->makeCrew($user, 'Rae', 'Kade', 'Active');
        $crew->addShipRole($captainRole);

        $this->em->persist($user);
        $this->em->persist($captainRole);
        $this->em->persist($crew);
        $this->em->flush();

        $repo = $this->makeCrewRepository();

        $withoutCaptain = $repo->findUnassignedForShip($user, [], 1, 10, false);
        self::assertCount(0, $withoutCaptain['items']);

        $withCaptain = $repo->findUnassignedForShip($user, [], 1, 10, true);
        self::assertCount(1, $withCaptain['items']);
    }

    public function testFindUnassignedSupportsFilters(): void
    {
        $user = $this->makeUser('crew-filters@log.test');
        $crewA = $this->makeCrew($user, 'Nova', 'Rao', 'Active')->setNickname('Spark');
        $crewB = $this->makeCrew($user, 'Quinn', 'Vale', 'Active')->setNickname('Ghost');

        $this->em->persist($user);
        $this->em->persist($crewA);
        $this->em->persist($crewB);
        $this->em->flush();

        $repo = $this->makeCrewRepository();

        $byName = $repo->findUnassignedForShip($user, ['search' => 'Nova'], 1, 10, true);
        self::assertCount(1, $byName['items']);
        self::assertSame('Nova', $byName['items'][0]->getName());

        $byNickname = $repo->findUnassignedForShip($user, ['nickname' => 'ghost'], 1, 10, true);
        self::assertCount(1, $byNickname['items']);
        self::assertSame('Ghost', $byNickname['items'][0]->getNickname());
    }

    public function testFindOneByCaptainOnShipExcludesSelf(): void
    {
        $user = $this->makeUser('crew-captain-ship@log.test');
        $ship = $this->makeShip($user, 'ISS Sentinel', 'Scout', 'S-1', '900000.00');
        $captainRole = $this->makeRole('CAP', 'Captain');

        $crewOne = $this->makeCrew($user, 'Kai', 'Rowan', 'Active')->setShip($ship);
        $crewOne->addShipRole($captainRole);

        $this->em->persist($user);
        $this->em->persist($ship);
        $this->em->persist($captainRole);
        $this->em->persist($crewOne);
        $this->em->flush();

        $repo = $this->makeCrewRepository();

        $found = $repo->findOneByCaptainOnShip($ship);
        self::assertNotNull($found);
        self::assertSame($crewOne->getId(), $found?->getId());

        $excluded = $repo->findOneByCaptainOnShip($ship, $crewOne);
        self::assertNull($excluded);
    }

    public function testPersistStatusDates(): void
    {
        $user = $this->makeUser('crew-dates@log.test');
        $crew = $this->makeCrew($user, 'Lira', 'Vance', 'On Leave')
            ->setOnLeaveDay(140)
            ->setOnLeaveYear(1105)
            ->setRetiredDay(null)
            ->setRetiredYear(null);

        $this->em->persist($user);
        $this->em->persist($crew);
        $this->em->flush();
        $crewId = $crew->getId();
        $this->em->clear();

        $saved = $this->em->find(Crew::class, $crewId);
        self::assertNotNull($saved);
        self::assertSame('On Leave', $saved->getStatus());
        self::assertSame(140, $saved->getOnLeaveDay());
        self::assertSame(1105, $saved->getOnLeaveYear());
    }

    private function makeUser(string $email): User
    {
        return (new User())
            ->setEmail($email)
            ->setPassword('hash');
    }

    private function makeShip(User $user, string $name, string $type, string $class, string $price): Ship
    {
        return (new Ship())
            ->setName($name)
            ->setType($type)
            ->setClass($class)
            ->setPrice($price)
            ->setUser($user);
    }

    private function makeRole(string $code, string $name): ShipRole
    {
        return (new ShipRole())
            ->setCode($code)
            ->setName($name)
            ->setDescription('Bridge duty role');
    }

    private function makeCrew(User $user, string $name, string $surname, ?string $status = null): Crew
    {
        $crew = (new Crew())
            ->setName($name)
            ->setSurname($surname)
            ->setUser($user);

        if ($status !== null) {
            $crew->setStatus($status);
        }

        return $crew;
    }

    private function makeCrewRepository(): CrewRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')
            ->with(Crew::class)
            ->willReturn($this->em);

        return new CrewRepository($registry);
    }
}
