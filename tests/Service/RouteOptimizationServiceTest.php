<?php

namespace App\Tests\Service;

use App\Service\RouteMathHelper;
use App\Service\RouteOptimizationService;
use App\Service\TravellerMapSectorLookup;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class RouteOptimizationServiceTest extends TestCase
{
    private RouteOptimizationService $service;
    private $lookupMock;

    protected function setUp(): void
    {
        $this->lookupMock = $this->createMock(TravellerMapSectorLookup::class);
        $mathHelper = new RouteMathHelper(); // Real logic is fine here, it's pure math
        $logger = new NullLogger();

        $this->service = new RouteOptimizationService($this->lookupMock, $mathHelper, $logger);
    }

    public function testFindShortestPathDirectJump(): void
    {
        // Verifica Logica: A -> B dist 2, Jump 2. Dovrebbe essere [A, B]
        $map = [
            '0101' => ['hex' => '0101'],
            '0103' => ['hex' => '0103'] // Dist 2
        ];

        $path = $this->service->findShortestPath($map, '0101', '0103', 2);

        $this->assertCount(2, $path);
        $this->assertEquals(['0101', '0103'], $path);
    }

    public function testFindShortestPathWithIntermediateStop(): void
    {
        // A -> C (Dist 4). Jump 2. Serve intermedio B (a dist 2).
        // 0101 -> 0103 -> 0105
        $map = [
            '0101' => ['hex' => '0101'],
            '0103' => ['hex' => '0103'],
            '0105' => ['hex' => '0105']
        ];

        // Jump 2. Diretto 0101->0105 è impossibile (dist 4). Deve fermarsi a 0103.
        $path = $this->service->findShortestPath($map, '0101', '0105', 2);

        $this->assertNotNull($path);
        $this->assertEquals(['0101', '0103', '0105'], $path);
    }

    public function testFindShortestPathUnreachable(): void
    {
        // A -> B (Dist 4). Jump 2. Nessun intermedio.
        $map = [
            '0101' => ['hex' => '0101'],
            '0105' => ['hex' => '0105']
        ];

        $path = $this->service->findShortestPath($map, '0101', '0105', 2);

        $this->assertNull($path);
    }

    public function testOptimizeMultiStopRouteTSP(): void
    {
        // Layout:
        // A(0101) --1--> B(0102) --1--> C(0103)
        // E un punto lontano D(0505) irraggiungibile
        // Testiamo rotta A -> {C, B}. 
        // Ottimo con Jump 1: A -> B -> C (totale 2 salti).
        // Se facessimo A -> C -> B: A->B->C (2 salti per arrivare a C) + C->B (1 salto) = 3 salti.

        $sectorData = [
            ['hex' => '0101', 'name' => 'A'],
            ['hex' => '0102', 'name' => 'B'],
            ['hex' => '0103', 'name' => 'C'],
        ];

        $this->lookupMock->method('parseSector')->willReturn($sectorData);

        // Partenza A. Dest: C, B. JumpRating 1.
        $result = $this->service->optimizeMultiStopRoute('TestSector', '0101', ['0103', '0102'], 1);

        // Miglior percorso dovrebbe essere A -> B -> C 
        // Nota: TSP ottimizza la visita di TUTTE le destinazioni.
        // A -> B (raggiunta Dest 1) -> C (raggiunta Dest 2). Distanza Totale: 2.
        // Oppure A -> B -> C (raggiunta C) -> B (raggiunta B). Distanza Totale: 3.

        // Asppetta, TSP riguarda visitare un INSIEME di destinazioni.
        // Se chiedo [C, B], l'ordine conta? No, tipicamente TSP trova l'ordine migliore.
        // L'algoritmo prova le permutazioni:
        // 1. Ordine [C, B]: Path A->..->C -> .. -> B
        // 2. Ordine [B, C]: Path A->..->B -> .. -> C

        // Caso 2 (A->B->C) costa: A->B(1) + B->C(1) = 2 salti.
        // Caso 1 (A->C->B) costa: A->B->C(2) + C->B(1) = 3 salti.

        // Quindi percorso atteso: 0101, 0102, 0103.
        $this->assertEquals(['0101', '0102', '0103'], $result['path']);
        $this->assertEquals(2, $result['total_jumps']);
    }

    public function testOptimizeMultiStopRouteHeuristic(): void
    {
        // Layout lineare: 0101 -> 0102 -> ... -> 0108
        $sectorData = [];
        for ($i = 1; $i <= 8; $i++) {
            $hex = sprintf('01%02d', $i);
            $sectorData[] = ['hex' => $hex, 'name' => 'System ' . $hex];
        }

        $this->lookupMock->method('parseSector')->willReturn($sectorData);

        // Destinazioni (7): Tutte tranne la partenza 0101, in ordine sparso
        $destinations = ['0108', '0103', '0105', '0102', '0107', '0104', '0106'];

        // Con N=7 dest, scatterà l'euristica Nearest Neighbor
        $result = $this->service->optimizeMultiStopRoute('TestSector', '0101', $destinations, 1);

        // Il percorso lineare dovrebbe comunque essere trovato (o quasi) dall'euristica
        // 0101 -> 0102 -> 0103 -> 0104 -> 0105 -> 0106 -> 0107 -> 0108
        $this->assertCount(8, $result['path']);
        $this->assertEquals(7, $result['total_jumps']);

        // Verifica che tutte le dest siano nel path
        foreach ($destinations as $dest) {
            $this->assertContains($dest, $result['path']);
        }
    }
}
