## 1. Indicizzatore Documenti
### Dipendenze aggiuntive da installare
```bash
composer require \
    smalot/pdfparser \
    phpoffice/phpword \
    openai-php/client \
    partitech/doctrine-pgvector
```
---
## 2. Open AI
### Nel file .env.local metti la chiave:
```env
OPENAI_API_KEY=sk-...
```
---
## 3. PostgreSQL + pgvector + Doctrine
### Installare postgres + pgvector
```bash
sudo apt install postgresql-18 postgresql-18-pgvector
```
### Nel database PostgreSQL (una volta sola)
sql
```sql
CREATE EXTENSION IF NOT EXISTS vector;
```
---
### In config/packages/doctrine.yaml aggiungi il tipo e le funzioni DQL:
yaml
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
---
## 4. Command per indicizzare
### Esempi di utilizzo
#### 1. Full index, sfruttando hash (solo file nuovi/modificati)
```bash
php bin/console app:index-docs -v
```
#### 2. Reindicizza TUTTO ignorando hash
```bash
php bin/console app:index-docs --force-reindex -v
```
#### 3. Solo la sotto-cartella manuali/
```bash
php bin/console app:index-docs --path=manuali --path=log/2025 -v
```
#### 4. Simulazione pura (solo vedere cosa succederebbe)
```bash
php bin/console app:index-docs --dry-run -v
```
#### 5. Indicizzare davvero, ma con embeddings finti (test locale)
```bash
php bin/console app:index-docs --test-mode -v
# oppure: APP_AI_TEST_MODE=true php bin/console app:index-docs -v
```