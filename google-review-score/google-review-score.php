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
    /**
     * The name of the block.
     * This needs to be the same as the folder and file name.
     */
    const NAME = 'google-review-score';

    /**
     * Extend the base context of our block.
     * With this function we can add for example a query or
     * other custom content.
     *
     * @param array $context      Holds the block data.
     * @return array  $context    Returns the array with the extra content that merges into the original block context.
     */

    public function block_context($context): array
    {

        $place_id = get_field('place_id') ? get_field('place_id') : null;
        $api_key = get_field('api_key') ? get_field('api_key') : null;
        $review_link = get_field('review_link');
        $link_to_reviews = get_field('show_reviews_link');
        $review_link = get_field('review_link');
        $button_text = get_field('button_text');

        $review_data = ($place_id && $api_key) ? $this->get_google_review_data($place_id, $api_key) : null;
        $context['data']['score'] = $review_data['rating'] ?? null;
        $context['data']['review_count'] = $review_data['total'] ?? null;
        $context['data']['review_link'] = $review_link;
        $context['data']['button_text'] = $button_text;
        $context['data']['link_to_reviews'] = $link_to_reviews;

        // $allowed_blocks  = apply_filters("wp-lemon/filter/block/{$this->slug}/allowed-blocks", ['core/heading', 'core/paragraph']);
        $args = [
            // 'InnerBlocks'     => '<InnerBlocks allowedBlocks="' . esc_attr(wp_json_encode($allowed_blocks)) . '" />',
        ];

        return array_merge($context, $args);
    }

    /**
     * Register fields to the block.
     *
     * @link https://github.com/StoutLogic/acf-builder
     * @return FieldsBuilder
     */

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
            ->addUrl('review_link', [
                'label' => 'Review link',
                'instructions' => 'Plak hier je reviewlink om een knop te laten zien zodat mensen een review kunnen plaatsen.',
                'required' => 0,
            ])
            ->addText('button_text', [
                'label' => 'Review button tekst',
                'instructions' => 'Als je iets anders dan de standaard wilt, voer hier dan je eigen tekst in.',
                'required' => 0,
            ])
            ->addUrl('show_reviews_link', [
                'label' => 'Link naar de Google reviews',
                'instructions' => 'Plak hier je de link om de reviews te lezen (is een hele lange)',
                'required' => 0,
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
            'total' => $body['result']['user_ratings_total'],
        ];

        set_transient($transient_key, $data, DAY_IN_SECONDS);

        return $data;
    }
}

/**
 * Initialiseer de block class
 */
new Google_Review_Score_Block();
