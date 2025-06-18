<?php

/**
 * ACF Block declaration
 *
 * @package WordPress
 * @subpackage WP_Lemon
 */

namespace WP_Lemon\Child\Blocks;

use HighGround\Bulldozer\BlockRendererV2 as BlockRenderer;
use StoutLogic\AcfBuilder\FieldsBuilder;

class Google_Review_Score_Block extends BlockRenderer
{
    const NAME = 'google-review-score';

    public function block_context($context): array
    {
        $block_id = $context['block']['id'] ?? null;

        $place_id = get_field('place_id', $block_id);
        $api_key = get_field('api_key', $block_id);
        $button_text = get_field('button_text', $block_id);

        $show_review_button = get_field('show_review_button', $block_id);
        $show_review_link = get_field('show_review_link', $block_id);

        // Genereer links automatisch
        $review_read_url = 'https://www.google.com/maps/place/?q=place_id:' . $place_id;
        $review_write_url = 'https://search.google.com/local/writereview?placeid=' . $place_id;

        // Review data ophalen
        $review_data = ($place_id && $api_key) ? $this->get_google_review_data($place_id, $api_key) : null;

        $context['data'] = array_merge($context['data'] ?? [], [
            'score'             => $review_data['rating'] ?? null,
            'review_count'      => $review_data['total'] ?? null,
            'review_read_url'   => $review_read_url,
            'review_write_url'  => $review_write_url,
            'button_text'       => $button_text ?: 'Review ons!',
            'show_review_button' => $show_review_button ?? 1,
            'show_review_link'  => $show_review_link ?? 1,
        ]);

        return $context;
    }

    public function add_fields(): FieldsBuilder
    {
        $this->registered_fields
            ->addText('place_id', [
                'label' => 'Google Place ID',
                'instructions' => 'Bijv. ChIJN1t_tDeuEmsRUsoyG83frY4',
                'required' => 1,
            ])
            ->addText('api_key', [
                'label' => 'Google API Key',
                'instructions' => 'Plak hier je Google Places API key.',
                'required' => 1,
            ])
            ->addText('button_text', [
                'label' => 'Review button tekst',
                'instructions' => 'Als je iets anders dan de standaard wilt, voer hier dan je eigen tekst in.',
                'required' => 0,
            ])
            ->addTrueFalse('show_review_button', [
                'label' => 'Toon review-knop',
                'ui'    => 1,
                'default_value' => 1,
            ])
            ->addTrueFalse('show_review_link', [
                'label' => 'Toon link naar alle reviews',
                'ui'    => 1,
                'default_value' => 1,
            ]);

        return $this->registered_fields;
    }

    private function get_google_review_data(string $place_id, string $api_key): ?array
    {
        $transient_key = 'google_review_' . md5($place_id);
        $cached = get_transient($transient_key);
        if ($cached !== false) {
            return $cached;
        }

        $fields = 'rating,user_ratings_total';
        $lang = 'nl';
        $url = "https://maps.googleapis.com/maps/api/place/details/json?placeid={$place_id}&fields={$fields}&language={$lang}&key={$api_key}";
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($body['result'])) {
            return null;
        }

        $data = [
            'rating' => $body['result']['rating'],
            'total'  => $body['result']['user_ratings_total'],
        ];

        set_transient($transient_key, $data, DAY_IN_SECONDS);

        return $data;
    }
}

new Google_Review_Score_Block();
