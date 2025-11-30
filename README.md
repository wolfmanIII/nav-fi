# Motore RAG(Retrieval-Augmented Generation)
## Definizione
Un motore AI RAG (o sistema RAG) è un’architettura che combina modelli di linguaggio (LLM) con un motore di ricerca interno per produrre risposte più accurate, verificabili e basate su dati propri.
## Dipendenze aggiuntive da installare
```bash
composer require \
    smalot/pdfparser \
    phpoffice/phpword \
    openai-php/client \
    partitech/doctrine-pgvector
```
## Nel file config/services.yaml
```yaml
services:
  ...
  Smalot\PdfParser\Parser: ~
```
# 2. Open AI
## Nel file .env.local metti la chiave:
```env
OPENAI_API_KEY=sk-...
```
# 3. PostgreSQL + pgvector + Doctrine
## Installare postgres + pgvector
```bash
sudo apt install postgresql-18 postgresql-18-pgvector
```
### Nel database PostgreSQL (una volta sola), necessari permessi di admin
sql
```sql
CREATE EXTENSION IF NOT EXISTS vector;
```
### Creare indice ivfflat per velocizzare le ricerche(dopo aver generato la entity DocumentChunk e relativa tabella su postgres)
```sql
CREATE INDEX IF NOT EXISTS document_chunk_embedding_ivfflat_idx
ON document_chunk
USING ivfflat (embedding vector_cosine_ops)
WITH (lists = 100)
```
## Cos'è pgvector
__pgvector__ è un’estensione per PostgreSQL che aggiunge:
* un tipo di colonna: vector(N) → un array di N numeri (float)
* operatori e funzioni per confrontare questi vettori (distanze, similarità)
* indici speciali (ivfflat / hnsw) per rendere le ricerche veloci

Nel nostro schema abbiamno:
```php
#[ORM\Column(type: 'vector', length: 1536)]
private array $embedding;
```
questo campo su __DocumentChunk__ è letteralmente:  
***il posto dove salviamo il vettore di embedding del chunk di testo***
## Definizione di embedding

Quando viene indicizzato un chunk:
* viene preso il testo (__$chunkText__)
* viene passato al modello __text-embedding-3-small__ di open-ai
* il modello restituisce un array di 1536 numeri tipo:
```json
[-0.023, 0.114, ..., 0.002]
```
Questo vettore rappresenta il significato del testo in uno spazio numerico.  
In questo spazio:  
Testi “simili” sono “vicini”; testi diversi sono “lontani”.

__pgvector__ serve esattamente a questo:  
Postgres li usa per memorizzare questi vettori e confrontarli.

All'interno dell'applicativo:
* quando indicizzi → salvi per ogni __DocumentChunk__ il suo __embedding__ (vector(1536))
* quando interroghi il chatbot → calcoli l’__embedding__ della domanda e lo confronti con quelli salvati.
## Cos’è cosine_similarity e cosa fa nella query
Nel ChatbotService abbiamo:
```php
$qb = $this->em->createQueryBuilder()
    ->select('c', 'f')
    ->from(DocumentChunk::class, 'c')
    ->join('c.file', 'f')
    ->where('c.embedding IS NOT NULL')
    ->orderBy('cosine_similarity(c.embedding, :vec)', 'DESC')
    ->setMaxResults(5)
    ->setParameter('vec', $queryVec);
```
Qui accadono 2 cose molto importanti:
1. __:vec__ è l’embedding della domanda (array di 1536 float).
2. __cosine_similarity(c.embedding, :vec)__ è una funzione di pgvector che calcola quanto sono simili i due vettori.
### Cos’è la cosine similarity in parole povere
Immagina ogni embedding come una freccia in uno spazio a 1536 dimensioni 😅

La ***cosine similarity*** misura l’angolo tra le due frecce(domanda, chunk):
* angolo piccolo → frecce “puntano” nella stessa direzione → __contenuti simili__
* angolo grande → frecce “puntano” in direzioni diverse → __contenuti diversi__

Il valore è tra -1 e 1:
* 1 → identici
* 0 → non correlati
* -1 → opposti

Quando si esegue:
```sql
ORDER BY cosine_similarity(c.embedding, :vec) DESC
```
si sta chiedendo:  
***“Recupera per primi i chunk il cui significato è più vicino al significato della domanda”.***
## Abilitare pgvector e cosine_similarity
### In config/packages/doctrine.yaml aggiungi il tipo e le funzioni DQL:
```yaml
doctrine:
  dbal:
    # ... il config solito (url, ecc.)
    types:
      vector: Partitech\DoctrinePgVector\Type\VectorType

  orm:
    # ...
    dql:
      string_functions:
        cosine_similarity: Partitech\DoctrinePgVector\Query\CosineSimilarity
        distance: Partitech\DoctrinePgVector\Query\Distance
```
### Abilitare le sonde sugli indici ivfflat(pgvector)
```yaml
services:
  # ...

  # Middleware per abilitare l'uso delle sonde sugli indici ivfflat(pgvector)
  App\Middleware\PgvectorIvfflatMiddleware:
    arguments:
      $probes: '%env(int:APP_IVFFLAT_PROBES)%'
```
Tramite la variabile di ambiente __APP_IVFFLAT_PROBES__:   
impostiamo il rapporto qualità velocità del nostro sistema RAG:  
* 5–10 = super veloce
* 20–30 = molto preciso
* 50–100 = qualità altissima (RAG più consistente, più lento)
# 4. Command per indicizzare per open-ai
## Esempi di utilizzo
### 1. Full index, sfruttando hash (solo file nuovi/modificati)
```bash
php bin/console app:index-docs -v
```
### 2. Reindicizza TUTTO ignorando hash
```bash
php bin/console app:index-docs --force-reindex -v
```
### 3. Solo la sotto-cartella manuali/
```bash
php bin/console app:index-docs --path=manuali --path=log/2025 -v
```
### 4. Simulazione pura (solo vedere cosa succederebbe)
```bash
php bin/console app:index-docs --dry-run -v
```
### 5. Indicizzare davvero, ma con embeddings finti (test locale)
```bash
php bin/console app:index-docs --test-mode -v
# oppure: APP_AI_TEST_MODE=true php bin/console app:index-docs -v
```
# 5. Command per vedere l'elenco dei file indicizzati
## Esempi di utilizzo
### 1. Elenco base (max 50):
```bash
php bin/console app:list-docs
```
### 2. Filtra per path (es. solo roba con “trast” nel nome):
```bash
php bin/console app:list-docs --path=trast
```
### 3. Mostra fino a 200 file:
```bash
php bin/console app:list-docs --limit=200
```
### 4. Path + limit insieme:
```bash
php bin/console app:list-docs --path=manuali --limit=20
```
# 5. Command per rimuovere file dell'indice
## Esempi di utilizzo
### 1. Eliminare un singolo file indicizzato
```bash
php bin/console app:unindex-file "manuali/helix.md"
```
### 2. Eliminare tutti i file sotto una cartella
```bash
php bin/console app:unindex-file "^manuali/"
```
### 3. Eliminare tutti i PDF
```bash
php bin/console app:unindex-file "\\.pdf$"
```
### 4. Eliminare TUTTO l’indice (equivalente a reset totale)
```bash
php bin/console app:unindex-file ".*"
```