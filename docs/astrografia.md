# Astrografia e Parsing Dati

## Panoramica
Il sistema Nav-Fi (The Cube) integra funzionalità avanzate per interpretare i dati grezzi provenienti dalle API di TravellerMap. Questo documento descrive come vengono decodificati e arricchiti i dati dei sistemi stellari.

## Decodifica UWP (`UwpDecoderService`)
L'Universal World Profile (UWP) è una stringa pseudo-esadecimale (es. `A788899-C`) che condensa le caratteristiche principali di un mondo.

### Componenti Decodificati
Il servizio `UwpDecoderService` scompone la stringa e fornisce descrizioni leggibili (in Inglese per l'UI) per:
- **Porto Spaziale (Starport)**: Qualità delle strutture (A=Excellent, X=None).
- **Dimensione (Size)**: Diametro e gravità.
- **Atmosfera**: Composizione e necessità di protezioni (es. "Dense, Tainted").
- **Idrografia**: Copertura d'acqua.
- **Popolazione**: Esponente base 10 (es. 9 = Miliardi).
- **Governo & Legge**: Struttura sociale e restrizioni.
- **Livello Tecnologico (TL)**: Avanzamento tecnologico (es. C=12, Average Stellar).

## Parsing Settoriale (`TravellerMapSectorLookup`)
Il servizio scarica i dati `.tab` (Tab Delimited) da TravellerMap e li trasforma in array strutturati.

### Supporto PBG
Oltre alle colonne standard (Hex, Name, UWP), il parser supporta la colonna **PBG** (Population multiplier, Belts, Gas giants), fondamentale per la logica di rifornimento.

| Codice | Significato | Chiave Array |
|--------|-------------|--------------|
| **P** | Moltiplicatore Popolazione | `pop_multiplier` (int) |
| **B** | Cinture di Asteroidi (Belts) | `belts` (int) |
| **G** | **Giganti Gassosi** | `gas_giants` (int) |

### Utilizzo Dati PBG
- **Gas Giants**: Usati per determinare la disponibilità di rifornimento gratuito ("Wilderness Refueling") per navi dotate di *Fuel Scoops*.
- **Belts**: Indicano potenziale minerario o pericoli di navigazione.
