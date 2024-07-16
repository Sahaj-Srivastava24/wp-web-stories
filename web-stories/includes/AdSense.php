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
     * API endpoint for fetching AdSense data.
     *
     * @var string
     */
    private string $api_endpoint;

    /**
     * Property code extracted from the host or query parameters.
     *
     * @var string
     */
    private string $property_code;

    /**
     * Analytics constructor.
     *
     * @since 1.12.0
     *
     * @param Settings $settings Settings instance.
     * @return void
     */
    public function __construct( Settings $settings ) {
        $this->settings = $settings;
        $this->property_code = $this->extract_property_code();
        $this->api_endpoint = $this->construct_api_endpoint();
    }

    /**
     * Initializes all hooks.
     *
     * @since 1.3.0
     */
    public function register(): void {
        add_action( 'web_stories_print_analytics', [ $this, 'print_adsense_tag' ] );
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
     * Extracts the property code from the current host or search parameters.
     *
     * @return string
     */
    private function extract_property_code(): string {
        $default_property_code = '4239'; // Default value if propertyCode is not set

        // Check if the property code is in the host
        if ( isset( $_SERVER['HTTP_HOST'] ) ) {
            $host = $_SERVER['HTTP_HOST'];
            $parts = explode('.', $host);

            // Assuming the property code is the first part of the host
            if ( isset( $parts[0] ) && is_numeric( $parts[0] ) ) {
                return $parts[0];
            }
        }

        // Check for search parameter called 'id'
        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            $query = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY );

            if ( $query ) {
                parse_str( $query, $query_params );

                if ( isset( $query_params['id'] ) && is_numeric( $query_params['id'] ) ) {
                    return $query_params['id'];
                }
            }
        }

        // Return the default property code if extraction fails
        return $default_property_code;
    }

    /**
     * Constructs the API endpoint based on the extracted property code.
     *
     * @return string
     */
    private function construct_api_endpoint(): string {
        return "https://gas.platform.gamezop.com/v3/sdk/ad-data?product=quizzop&propertyCode={$this->property_code}";
    }

    /**
     * Fetches AdSense data from the API.
     *
     * @return array|null
     */
    private function fetch_adsense_data(): ?array {
        $response = wp_remote_get( $this->api_endpoint );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! isset( $data['data']['adConfig']['adsenseClientId'] ) ) {
            return null;
        }

        $adsense_client_id = $data['data']['adConfig']['adsenseClientId'];
        $parts = explode( '|', $adsense_client_id );

        if ( count( $parts ) !== 2 ) {
            return null;
        }

        return [
            'client' => $parts[0],
            'slot'   => $parts[1],
        ];
    }

    /**
     * Prints the <amp-story-auto-ads> tag for single stories.
     *
     * @since 1.3.0
     */
    public function print_adsense_tag(): void {
        $publisher = $this->get_publisher_id();
        $slot      = $this->get_slot_id();
        $enabled   = $this->is_enabled();

        if ( ! $enabled || ! $publisher || ! $slot ) {
            return;
        }

        // Fetch AdSense data from the API
        $adsense_data = $this->fetch_adsense_data();

        if ( ! $adsense_data ) {
            return;
        }

        $data_ad_client = esc_attr( $adsense_data['client'] );
        $data_ad_slot   = esc_attr( $adsense_data['slot'] );

        ?>
        <amp-story-auto-ads>
            <script type="application/json">
                {
                    "version": "v0.4",
                    "propertyCode": "<?php echo $this->property_code; ?>",
                    "ad-attributes": {
                        "type": "adsense",
                        "data-ad-client": "<?php echo $data_ad_client; ?>",
                        "data-ad-slot": "<?php echo $data_ad_slot; ?>"
                    }
                }
            </script>
        </amp-story-auto-ads>
        <?php
    }

    /**
     * Returns the Google AdSense publisher ID.
     *
     * @since 1.3.0
     *
     * @return string Publisher ID.
     */
    private function get_publisher_id(): string {
        /**
         * Publisher ID.
         *
         * @var string $publisher_id
         */
        $publisher_id = $this->settings->get_setting( $this->settings::SETTING_NAME_ADSENSE_PUBLISHER_ID );
        return $publisher_id;
    }

    /**
     * Returns the Google AdSense slot ID.
     *
     * @since 1.3.0
     *
     * @return string Slot ID.
     */
    private function get_slot_id(): string {
        /**
         * Slot ID.
         *
         * @var string
         */
        return $this->settings->get_setting( $this->settings::SETTING_NAME_ADSENSE_SLOT_ID );
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
