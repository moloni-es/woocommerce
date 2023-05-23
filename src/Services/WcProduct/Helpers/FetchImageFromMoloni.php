<?php

namespace MoloniES\Services\WcProduct\Helpers;

class FetchImageFromMoloni
{
    private $img;

    public function __construct(string $img)
    {
        $this->img = $img;
    }

    public function get()
    {
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $imageUrl = 'https://mediaapi.moloni.org' . $this->img;
        $uploadDir = wp_upload_dir();
        $imgRequest = wp_remote_get($imageUrl);

        if (is_wp_error($imgRequest)) {
            return 0;
        }

        $image_data = $imgRequest['body'];
        $filename = basename($imageUrl);

        if (wp_mkdir_p($uploadDir['path'])) {
            $file = $uploadDir['path'] . '/' . $filename;
        } else {
            $file = $uploadDir['basedir'] . '/' . $filename;
        }

        file_put_contents($file, $image_data);

        $wpFiletype = wp_check_filetype($filename, null);

        $attachment = [
            'post_mime_type' => $wpFiletype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $imageId = wp_insert_attachment($attachment, $file);
        $attachData = wp_generate_attachment_metadata($imageId, $file);

        wp_update_attachment_metadata($imageId, $attachData);

        return $imageId;
    }
}