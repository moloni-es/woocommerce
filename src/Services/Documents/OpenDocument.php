<?php

namespace MoloniES\Services\Documents;

use MoloniES\API\Documents;
use MoloniES\Exceptions\APIExeption;

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
        } catch (APIExeption $e) {}
    }

    /**
     * Service runner
     *
     * @throws APIExeption
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
