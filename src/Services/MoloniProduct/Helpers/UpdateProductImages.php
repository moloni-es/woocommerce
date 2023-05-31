<?php

namespace MoloniES\Services\MoloniProduct\Helpers;

use MoloniES\Curl;
use MoloniES\Storage;
use MoloniES\Enums\Boolean;

class UpdateProductImages
{
    private $images;
    private $moloniProduct;
    private $boundary = 'MOLONIRULES';

    public function __construct(?array $images = [], ?array $moloniProduct = [])
    {
        $this->images = $images;
        $this->moloniProduct = $moloniProduct;

        $this->handle();
    }

    private function handle(): void
    {
        if (empty($this->images)) {
            return;
        }

        $headers = [
            'Authorization' => 'Bearer ' . Storage::$MOLONI_ES_ACCESS_TOKEN,
            'Content-type' => 'multipart/form-data; boundary=' . $this->boundary,
        ];
        $query = str_replace(["\n", "\r"], '', $this->getMutation());
        $payload = '';

        $variables = [
            'companyId' => Storage::$MOLONI_ES_COMPANY_ID,
            'data' => [
                'productId' => $this->moloniProduct['productId'],
                'img' => empty($this->images[0]) ? null : '{' . 0 . '}',
                'variants' => [],
            ]
        ];

        $map = '{ "0": ["variables.data.img"]';

        if (!empty($this->moloniProduct['variants'])) {
            foreach ($this->moloniProduct['variants'] as $idx => $variant) {
                $variantId = (int)$variant['productId'];

                if ((int)$variant['visible'] === Boolean::NO) {
                    $variables['data']['variants'][] = [
                        'productId' => $variantId
                    ];

                    continue;
                }

                if (!isset($this->images[$variantId]) || empty($this->images[$variantId])) {
                    $variables['data']['variants'][] = [
                        'productId' => $variantId,
                        'img' => null
                    ];

                    continue;
                }

                $map .= ', "' . $variantId . '": ["variables.data.variants.' . $idx . '.img"]';

                $variables['data']['variants'][] = [
                    'productId' => $variant['productId'],
                    'img' => '{' . $variantId . '}',
                ];
            }
        }

        $map .= ' }';

        $data = [
            'operations' => json_encode(['query' => $query, 'variables' => $variables]),
            'map' => $map
        ];

        foreach ($data as $name => $value) {
            $payload .= '--' . $this->boundary;
            $payload .= "\r\n";
            $payload .= 'Content-Disposition: form-data; name="' . $name .
                '"' . "\r\n\r\n";
            $payload .= $value;
            $payload .= "\r\n";
        }

        foreach ($this->images as $id => $image) {
            if (empty($image)) {
                continue;
            }

            $payload .= '--' . $this->boundary;
            $payload .= "\r\n";
            $payload .= 'Content-Disposition: form-data; name="' . $id . '"; filename="' . basename($image) . '"' . "\r\n";
            $payload .= 'Content-Type: image/*' . "\r\n";
            $payload .= "\r\n";
            $payload .= file_get_contents($image);
            $payload .= "\r\n";
        }

        $payload .= '--' . $this->boundary . '--';

        Curl::simpleCustomPost($headers, $payload);
    }

    private function getMutation(): string
    {
        return 'mutation productUpdate($companyId: Int!,$data: ProductUpdate!)
        {
            productUpdate(companyId: $companyId ,data: $data)
            {
                data
                {
                    productId
                    name
                    reference
                }
                errors
                {
                    field
                    msg
                }
            }
        }';
    }
}