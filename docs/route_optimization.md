# Servizio di Ottimizzazione Rotte (RouteOptimizationService)

## Panoramica
Il `RouteOptimizationService` è un componente core progettato per calcolare percorsi di navigazione interstellare ottimali.
Risolve due problemi principali:
1.  **Pathfinding (A*)**: Trovare il percorso più breve tra due sistemi stellari, inserendo tappe intermedie se la distanza supera la capacità di salto (`Jump Rating`) della nave.
2.  **TSP (Traveling Salesman Problem)**: Data una lista di destinazioni multiple, determinare l'ordine di visita più efficiente per minimizzare i salti totali.

## Dipendenze
- `TravellerMapSectorLookup`: Fornisce i dati astrografici del settore (coordinate Hex).
- `RouteMathHelper`: Fornisce le primitive matematiche per il calcolo delle distanze in una griglia esagonale.

## Funzionalità Principali

### 1. `findShortestPath`
Calcola il percorso lineare tra due sistemi.
- **Input**: Mappa dei sistemi, Hex Partenza, Hex Arrivo, Jump Rating.
- **Algoritmo**: A* (A-Star).
- **Output**: Array di Hex che rappresentano la sequenza di sistemi da visitare.

### 2. `optimizeMultiStopRoute`
Calcola la rotta complessa per visitare N destinazioni.
- **Input**: Nome Settore, Hex Partenza, Array di Hex Destinazioni, Jump Rating.
- **Algoritmo**: Permutazioni (Brute force ottimizzato per N < 10).
- **Logica**:
    1. Genera tutte le possibili sequenze di destinazioni.
    2. Per ogni sequenza, calcola il percorso reale sommando i sotto-percorsi A* tra le tappe.
    3. Seleziona la sequenza con il numero totale di salti minore.
- **Output**: Array contenente la rotta completa (inclusi waypoint intermedi) e il costo totale in salti.

## Esempio di Utilizzo

```php
$service = $container->get(RouteOptimizationService::class);

// Calcola rotta ottimale per visitare 3 sistemi con una nave J-2
$result = $service->optimizeMultiStopRoute(
    'Spinward Marches',
    '1910',              // Partenza (Regina)
    ['1705', '2104'],    // Destinazioni (Efate, Louzy)
    2                    // Jump-2
);

// $result['path'] conterrà la sequenza ordinata di hex:
// ['1910', '1909', '1908', ... '1705', ... '2104']
```

## Note Tecniche
- **Efficienza**: L'algoritmo scarica e analizza l'intero settore una volta sola per richiesta.
- **Limiti**: Attualmente assume che i salti avvengano sempre tra sistemi conosciuti (non supporta salti nel vuoto "deep space").
