<?php
/**
 * Class AdSense
 *
 * @link      https://github.com/googleforcreators/web-stories-wp
 *
 * @copyright 2020 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

/**
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types = 1);

namespace Google\Web_Stories;

use Google\Web_Stories\Infrastructure\HasRequirements;

/**
 * Class AdSense
 */
class AdSense extends Service_Base implements HasRequirements {
    /**
     * Settings instance.
     *
     * @var Settings Settings instance.
     */
    private Settings $settings;

    /**
     * AdSense constructor.
     *
     * @since 1.12.0
     *
     * @param Settings $settings Settings instance.
     * @return void
     */
    public function __construct( Settings $settings ) {
        $this->settings = $settings;
    }

    /**
     * Initializes all hooks.
     *
     * @since 1.3.0
     */
    public function register(): void {
        add_action( 'web_stories_print_adsense', [ $this, 'print_adsense_tag' ], 10, 2 );
    }

    /**
     * Get the list of service IDs required for this service to be registered.
     *
     * Needed because settings needs to be registered first.
     *
     * @since 1.13.0
     *
     * @return string[] List of required services.
     */
    public static function get_requirements(): array {
        return [ 'settings' ];
    }

    /**
     * Prints the <amp-story-auto-ads> tag for single stories.
     *
     * @since 1.3.0
     *
     * @param string $data_ad_client The ad client ID to be used.
     * @param string $data_ad_slot The ad slot ID to be used.
     */
    public function print_adsense_tag( string $data_ad_client, string $data_ad_slot ): void {
        if ( ! $data_ad_client || ! $data_ad_slot ) {
            echo "<script>console.log('WP-WEB-STORIES:: data_ad_client | data_ad_slot is not found for adsense');</script>";
            return;
        }

        ?>
        <amp-story-auto-ads>
            <script type="application/json">
                {
                    "ad-attributes": {
                        "type": "adsense",
                        "data-ad-client": "<?php echo esc_attr( $data_ad_client ); ?>",
                        "data-ad-slot": "<?php echo esc_attr( $data_ad_slot ); ?>"
                    }
                }
            </script>
        </amp-story-auto-ads>
        <?php
    }

    /**
     * Returns if Google AdSense is enabled.
     *
     * @since 1.3.0
     */
    private function is_enabled(): bool {
        return ( 'adsense' === $this->settings->get_setting( $this->settings::SETTING_NAME_AD_NETWORK, 'none' ) );
    }
}
