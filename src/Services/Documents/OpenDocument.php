<?php

namespace MoloniES\Services\Documents;

use MoloniES\API\Documents;
use MoloniES\Exceptions\Error;

class OpenDocument
{
    private $documentId;

    /**
     * Construct
     *
     * @param $documentId
     */
    public function __construct($documentId)
    {
        $this->documentId = $documentId;

        try {
            $this->run();
        } catch (Error $e) {}
    }

    /**
     * Service runner
     *
     * @throws Error
     */
    private function run(): void
    {
        $variables = [
            'documentId' => $this->documentId
        ];

        $invoice = Documents::queryDocument($variables);

        if (isset($invoice['errors']) || !isset($invoice['data']['document']['data']['documentId'])) {
            return;
        }

        $invoice = $invoice['data']['document']['data'];

        header('Location: https://ac.moloni.es/' . $invoice['company']['slug'] . '/' . $invoice['documentType']['apiCodePlural'] . '/view/' . $invoice['documentId']);

        exit;
    }
}