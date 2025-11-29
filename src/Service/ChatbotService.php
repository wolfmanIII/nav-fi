<?php

namespace App\Service;

use App\Entity\DocumentChunk;
use Doctrine\ORM\EntityManagerInterface;
use OpenAI;

class ChatbotService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function ask(string $question): string
    {
        $testMode = ($_ENV['APP_AI_TEST_MODE'] ?? 'false') === 'true';
        $offlineFallbackEnabled =
            ($_ENV['APP_AI_OFFLINE_FALLBACK'] ?? 'true') === 'true';

        // In test-mode NON chiama OpenAI, usa solo il DB
        if ($testMode) {
            return $this->answerInTestMode($question);
        }

        try {
            $client = OpenAI::client($_ENV['OPENAI_API_KEY']);

            // 1) Embedding della domanda
            $embResp = $client->embeddings()->create([
                'model' => 'text-embedding-3-small',
                'input' => $question,
                // se si usa vector(1536) non serve "dimensions"
                // 'dimensions' => 1536,
            ]);
            $queryVec = $embResp->embeddings[0]->embedding;

            // 2) Recupero chunk più simili (top 5)
            $qb = $this->em->createQueryBuilder()
                ->select('c')
                ->from(DocumentChunk::class, 'c')
                ->where('c.embedding IS NOT NULL')
                ->orderBy('cosine_similarity(c.embedding, :vec)', 'DESC')
                ->setMaxResults(5)
                ->setParameter('vec', $queryVec);

            /** @var DocumentChunk[] $chunks */
            $chunks = $qb->getQuery()->getResult();

            if (!$chunks) {
                return 'Non trovo informazioni rilevanti nei documenti indicizzati.';
            }

            $context = '';
            foreach ($chunks as $chunk) {
                $context .= "Fonte: {$chunk->getPath()} (chunk {$chunk->getChunkIndex()})\n";
                $context .= $chunk->getContent() . "\n\n";
            }

            $system = <<<TXT
Sei un assistente che risponde SOLO usando le informazioni nei documenti forniti.
Se la risposta non è presente nei documenti, devi dire chiaramente che non trovi
l'informazione nei documenti indicizzati. Rispondi nella stessa lingua della domanda.
TXT;

            $user = <<<TXT
DOCUMENTAZIONE:
{$context}

DOMANDA:
{$question}
TXT;

            // 3) Chiamata al modello
            $resp = $client->chat()->create([
                'model' => 'gpt-5.1-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'max_tokens' => 400,
            ]);

            return $resp->choices[0]->message->content ?? '';
        } catch (\Throwable $e) {
            if ($offlineFallbackEnabled) {
                return $this->answerInOfflineFallback($question, $e);
            }

            return 'Errore nella chiamata al servizio AI: '.$e->getMessage();
        }
    }

    /**
     * Modalità test: nessuna chiamata ad OpenAI.
     * Usa una ricerca a keyword (OR) nel contenuto dei chunk.
     */
    private function answerInTestMode(string $question): string
    {
        $keywords = $this->buildKeywords($question);

        $qb = $this->em->createQueryBuilder()
            ->select('c')
            ->from(DocumentChunk::class, 'c')
            ->setMaxResults(5);

        if ($keywords) {
            // costruisco un OR: LOWER(c.content) LIKE :k0 OR :k1 ...
            $expr = $qb->expr();
            $orX  = $expr->orX();

            foreach ($keywords as $idx => $kw) {
                $paramName = 'k'.$idx;
                $orX->add($expr->like('LOWER(c.content)', ':'.$paramName));
                $qb->setParameter($paramName, '%'.$kw.'%');
            }

            $qb->where($orX);
        } else {
            // fallback: LIKE sull'intera domanda (caso limite)
            $qb->where('LOWER(c.content) LIKE :q')
               ->setParameter('q', '%'.mb_strtolower($question).'%');
        }

        /** @var DocumentChunk[] $chunks */
        $chunks = $qb->getQuery()->getResult();

        if (!$chunks) {
            return "[TEST MODE] Nessun documento sembra contenere la query.\n\nDomanda: ".$question;
        }

        $out = "[TEST MODE] Non sto chiamando OpenAI.\n";
        $out .= "Questi sono alcuni estratti che sembrano rilevanti:\n\n";

        foreach ($chunks as $chunk) {
            $preview = mb_substr($chunk->getContent(), 0, 300);
            $out .= "- Fonte: {$chunk->getPath()} (chunk {$chunk->getChunkIndex()})\n";
            $out .= "  Estratto: ".str_replace("\n", ' ', $preview)."…\n\n";
        }

        return $out;
    }

    /**
     * Modalità fallback offline: si attiva se la chiamata ad OpenAI fallisce.
     * Usa gli stessi keyword della modalità test-mode per cercare nel DB locale.
     */
    private function answerInOfflineFallback(string $question, \Throwable $e): string
    {
        $keywords = $this->buildKeywords($question);

        $qb = $this->em->createQueryBuilder()
            ->select('c')
            ->from(DocumentChunk::class, 'c')
            ->setMaxResults(5);

        if ($keywords) {
            $expr = $qb->expr();
            $orX  = $expr->orX();

            foreach ($keywords as $idx => $kw) {
                $paramName = 'k'.$idx;
                $orX->add($expr->like('LOWER(c.content)', ':'.$paramName));
                $qb->setParameter($paramName, '%'.$kw.'%');
            }

            $qb->where($orX);
        } else {
            $qb->where('LOWER(c.content) LIKE :q')
               ->setParameter('q', '%'.mb_strtolower($question).'%');
        }

        /** @var DocumentChunk[] $chunks */
        $chunks = $qb->getQuery()->getResult();

        if (!$chunks) {
            return "Il servizio AI non è raggiungibile e non trovo nulla nei documenti locali per la tua domanda.\n"
                 . "Dettaglio tecnico: ".$e->getMessage();
        }

        $out = "Il servizio AI non è raggiungibile in questo momento, "
             . "ma ho trovato alcuni estratti nei documenti locali:\n\n";

        foreach ($chunks as $chunk) {
            $preview = mb_substr($chunk->getContent(), 0, 300);
            $out .= "- Fonte: {$chunk->getPath()} (chunk {$chunk->getChunkIndex()})\n";
            $out .= "  Estratto: ".str_replace("\n", ' ', $preview)."…\n\n";
        }

        $out .= "\n(Dettaglio tecnico: ".$e->getMessage().")";

        return $out;
    }

    /**
     * Estrae keyword significative dalla domanda:
     * - minuscole
     * - rimuove punteggiatura
     * - tiene solo parole con almeno 3 caratteri
     * - rimuove duplicati
     *
     * Es:
     *   "Chi è M. Trast?" → ["trast"]
     *   "Dimmi di Malen Trast e della sua nave" → ["dimmi", "malen", "trast", "nave"]
     */
    private function buildKeywords(string $text): array
    {
        $text = mb_strtolower($text);

        // Sostitusce tutto ciò che NON è lettera/numero/spazio con spazio
        // \p{L} = tutte le lettere Unicode, \p{N} = numeri
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        if ($text === null) {
            return [];
        }

        $parts = preg_split('/\s+/', trim($text));
        if (!$parts) {
            return [];
        }

        $keywords = [];
        foreach ($parts as $p) {
            if (mb_strlen($p) < 3) {
                continue; // scarta parole troppo corte tipo "è", "di", "a"
            }
            $keywords[] = $p;
        }

        // Rimuovi duplicati
        $keywords = array_values(array_unique($keywords));

        return $keywords;
    }
}
