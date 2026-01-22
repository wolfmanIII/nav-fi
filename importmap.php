<?php

/**
 * Restituisce l'importmap per questa applicazione.
 *
 * - "path" è un percorso all'interno del sistema asset mapper. Usa il
 *     comando "debug:asset-map" per vedere l'elenco completo dei percorsi.
 *
 * - "entrypoint" (solo JavaScript) impostato a true per qualsiasi modulo che
 *     verrà usato come "entrypoint" (e passato alla funzione Twig importmap()).
 *
 * Il comando "importmap:require" può essere usato per aggiungere nuove voci a questo file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
];
