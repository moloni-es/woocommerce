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
                'img' => empty($this->images[0]['file']) ? null : '{' . 0 . '}',
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

                if (!isset($this->images[$variantId]) || empty($this->images[$variantId]['file'])) {
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

        foreach ($this->images as $id => $imageData) {
            if (empty($imageData['file'])) {
                continue;
            }

            $payload .= '--' . $this->boundary;
            $payload .= "\r\n";
            $payload .= 'Content-Disposition: form-data; name="' . $id . '"; filename="' . basename($imageData['file']) . '"' . "\r\n";
            $payload .= 'Content-Type: image/*' . "\r\n";
            $payload .= "\r\n";
            $payload .= file_get_contents($imageData['file']);
            $payload .= "\r\n";
        }

        $payload .= '--' . $this->boundary . '--';

        $post = Curl::simpleCustomPost($headers, $payload);

        if (is_wp_error($post)) {
            return;
        }

        $updatedProduct = $post['body']['data']['productUpdate']['data'] ?? [];

        if (empty($updatedProduct)) {
            return;
        }

        $this->saveImagesMetaTag($updatedProduct);
    }

    private function saveImagesMetaTag($updatedProduct)
    {
        if (!empty($updatedProduct['img']) && !empty($this->images[0]['id'])) {
            update_post_meta((int)$this->images[0]['id'], '_moloni_file_name', $updatedProduct['img']);
        }

        if (empty($this->moloniProduct['variants'])) {
            return;
        }

        foreach ($updatedProduct['variants'] as $variant) {
            if (empty($variant['img'])) {
                continue;
            }

            $imageId = ($this->images[(int)$variant['productId']]['id'] ?? 0);

            if (empty($imageId)) {
                continue;
            }

            update_post_meta($imageId, '_moloni_file_name', $variant['img']);
        }
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
                    img
                    variants
                    {
                        productId
                        img
                    }
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
