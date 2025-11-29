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

        if ($testMode) {
            return $this->answerInTestMode($question);
        }

        // Proviamo a usare embeddings + modello
        try {
            $client = OpenAI::client($_ENV['OPENAI_API_KEY']);

            // 1) Embedding della domanda
            $embResp = $client->embeddings()->create([
                'model' => 'text-embedding-3-small',
                'input' => $question,
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
                'model' => 'gpt-4o-mini',
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

            // fallback "brutto" se disattivi la modalità offline
            return 'Errore nella chiamata al servizio AI: '.$e->getMessage();
        }
    }

    /**
     * Modalità test: nessuna chiamata ad OpenAI.
     * Fa una ricerca full-text semplice e restituisce un testo di debug.
     */
    private function answerInTestMode(string $question): string
    {
        $qb = $this->em->createQueryBuilder()
            ->select('c')
            ->from(DocumentChunk::class, 'c')
            ->where('LOWER(c.content) LIKE :q')
            ->setMaxResults(5)
            ->setParameter('q', '%'.mb_strtolower($question).'%');

        /** @var DocumentChunk[] $chunks */
        $chunks = $qb->getQuery()->getResult();

        if (!$chunks) {
            return "[TEST MODE] Nessun documento sembra contenere la query.\n\nDomanda: ".$question;
        }

        $out = "[TEST MODE] Non sto chiamando OpenAI.\n"
             . "Questi sono alcuni estratti che sembrano rilevanti:\n\n";

        foreach ($chunks as $chunk) {
            $preview = mb_substr($chunk->getContent(), 0, 300);
            $out .= "- Fonte: {$chunk->getPath()} (chunk {$chunk->getChunkIndex()})\n";
            $out .= "  Estratto: ".str_replace("\n", ' ', $preview)."…\n\n";
        }

        return $out;
    }

    /**
     * Modalità fallback offline: si attiva se la chiamata ad OpenAI fallisce.
     * Usa solo il DB locale per dare qualche info utile.
     */
    private function answerInOfflineFallback(string $question, \Throwable $e): string
    {
        $qb = $this->em->createQueryBuilder()
            ->select('c')
            ->from(DocumentChunk::class, 'c')
            ->where('LOWER(c.content) LIKE :q')
            ->setMaxResults(5)
            ->setParameter('q', '%'.mb_strtolower($question).'%');

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
}
