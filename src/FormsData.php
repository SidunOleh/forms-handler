<?php

namespace FormsHandler;

defined('ABSPATH') or die;

class FormsData
{
    public static function save(
        string $form, 
        bool $status, 
        array $data
    ): int|false
    {
        global $wpdb;

        $dataToInsert = [];
        $dataToInsert['form'] = $form;
        $dataToInsert['status'] = $status;
        $dataToInsert['data'] = json_encode($data);
        $dataToInsert['created_at'] = date('Y-m-d H:i:s');

        return $wpdb->insert("{$wpdb->base_prefix}forms_data", $dataToInsert);
    }

    public static function get(int $page, int $size): array
    {
        global $wpdb;

        $offset = ($page - 1) * $size;

        $formsData = $wpdb->get_results("SELECT * 
            FROM `{$wpdb->base_prefix}forms_data`
            ORDER BY `created_at` DESC
            LIMIT {$size}
            OFFSET {$offset}", ARRAY_A);

        foreach ($formsData as &$item) {
            $data = json_decode($item['data'], true) ?? [];
            foreach ($data as $name => $value) {
                $item[$name] = $value;
            }

            unset($item['data']);

            $item['created_at'] = 
                date('m.d.Y H:i', strtotime($item['created_at']));
        }

        return $formsData;
    }

    public static function total(): int
    {
        global $wpdb;

        $total = $wpdb->get_var("SELECT COUNT(*)
            FROM `{$wpdb->base_prefix}forms_data`");

        return $total;
    }

    public static function delete(int $id): int|false
    {
        global $wpdb;

        return $wpdb->delete("{$wpdb->base_prefix}forms_data", ['id' => $id,]);
    }
}