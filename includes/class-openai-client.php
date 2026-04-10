<?php

declare(strict_types=1);

namespace WpAiEdit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class OpenAIClient {

    private readonly string $endpoint;
    private readonly string $api_key;
    private readonly string $model;

    public function __construct( string $endpoint, string $api_key, string $model ) {
        $this->endpoint = rtrim( $endpoint, '/' );
        $this->api_key  = $api_key;
        $this->model    = $model;
    }

    /**
     * Validate that the endpoint URL is safe (HTTPS only, no private IPs).
     */
    public static function validate_endpoint( string $url ): bool {
        $parsed = wp_parse_url( $url );
        if ( ! is_array( $parsed ) ) {
            return false;
        }

        // Must be HTTPS
        $scheme = strtolower( $parsed['scheme'] ?? '' );
        if ( $scheme !== 'https' ) {
            return false;
        }

        $host = $parsed['host'] ?? '';
        if ( $host === '' ) {
            return false;
        }

        // Block private/reserved IP ranges
        $ip = gethostbyname( $host );
        if ( $ip !== $host ) { // resolved to IP
            $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
            if ( filter_var( $ip, FILTER_VALIDATE_IP, $flags ) === false ) {
                return false;
            }
        }

        // Block localhost variants
        $lower_host = strtolower( $host );
        if ( in_array( $lower_host, [ 'localhost', '127.0.0.1', '::1', '0.0.0.0' ], true ) ) {
            return false;
        }

        return true;
    }

    public static function from_settings(): ?self {
        $settings = get_option( 'wp_ai_edit_settings', [] );

        if ( ! is_array( $settings ) ) {
            return null;
        }

        $endpoint  = $settings['endpoint'] ?? '';
        $encrypted = $settings['api_key_encrypted'] ?? '';
        $model     = $settings['model'] ?? 'gpt-5.4';

        if ( $endpoint === '' || $encrypted === '' ) {
            return null;
        }

        if ( ! self::validate_endpoint( $endpoint ) ) {
            return null;
        }

        $api_key = self::decrypt_api_key( $encrypted );
        if ( $api_key === null ) {
            return null;
        }

        return new self( $endpoint, $api_key, $model );
    }

    public static function from_config( string $endpoint, string $api_key, string $model ): ?self {
        $normalized_endpoint = rtrim( sanitize_url( $endpoint ), '/' );
        $normalized_model    = sanitize_text_field( $model );

        if ( $normalized_endpoint === '' || $api_key === '' || $normalized_model === '' ) {
            return null;
        }

        if ( ! self::validate_endpoint( $normalized_endpoint ) ) {
            return null;
        }

        return new self( $normalized_endpoint, $api_key, $normalized_model );
    }

    /**
     * Stream a chat completion response via SSE.
     *
     * @param array{role: string, content: string}[] $messages
     */
    public function stream_chat( array $messages, int $max_tokens = 2048 ): void {
        $url = $this->endpoint . '/chat/completions';

        $body = wp_json_encode( [
            'model'                => $this->model,
            'messages'             => $messages,
            'stream'               => true,
            'max_completion_tokens' => $max_tokens,
        ] );

        if ( $body === false ) {
            self::send_sse_error( __( 'Failed to encode request body.', 'wp-ai-edit' ) );
            return;
        }

        $ch = curl_init();

        if ( $ch === false ) {
            self::send_sse_error( __( 'Failed to initialize cURL.', 'wp-ai-edit' ) );
            return;
        }

        curl_setopt_array( $ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'api-key: ' . $this->api_key,
                'Authorization: Bearer ' . $this->api_key,
            ],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_WRITEFUNCTION  => static function ( $ch, string $data ) : int {
                $lines = explode( "\n", $data );

                foreach ( $lines as $line ) {
                    $line = trim( $line );

                    if ( $line === '' ) {
                        continue;
                    }

                    if ( ! str_starts_with( $line, 'data: ' ) ) {
                        continue;
                    }

                    $json_str = substr( $line, 6 );

                    if ( $json_str === '[DONE]' ) {
                        echo "data: [DONE]\n\n";
                        self::flush_output();
                        continue;
                    }

                    $payload = json_decode( $json_str, true );
                    if ( ! is_array( $payload ) ) {
                        continue;
                    }

                    $delta = $payload['choices'][0]['delta']['content'] ?? null;
                    if ( $delta !== null ) {
                        $sse_data = wp_json_encode( [ 'content' => $delta ] );
                        echo "data: {$sse_data}\n\n";
                        self::flush_output();
                    }
                }

                return strlen( $data );
            },
        ] );

        $result = curl_exec( $ch );

        if ( $result === false ) {
            $error = curl_error( $ch );
            $errno = curl_errno( $ch );
            curl_close( $ch );
            self::send_sse_error(
                sprintf(
                    /* translators: 1: error number, 2: error message */
                    __( 'cURL error (%1$d): %2$s', 'wp-ai-edit' ),
                    $errno,
                    $error
                )
            );
            return;
        }

        $http_code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );

        if ( $http_code >= 400 ) {
            self::send_sse_error(
                sprintf(
                    /* translators: %d: HTTP status code */
                    __( 'API error: HTTP %d', 'wp-ai-edit' ),
                    $http_code
                )
            );
        }
    }

    /**
     * Test the API connection with a simple request.
     *
     * @return array{success: bool, message: string}
     */
    public function test_connection(): array {
        $url = $this->endpoint . '/chat/completions';

        $body = wp_json_encode( [
            'model'      => $this->model,
            'messages'   => [
                [ 'role' => 'user', 'content' => 'Say "OK".' ],
            ],
            'max_completion_tokens' => 5,
        ] );

        $response = wp_remote_post( $url, [
            'timeout' => 15,
            'headers' => [
                'Content-Type'  => 'application/json',
                'api-key'       => $this->api_key,
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'body' => $body,
        ] );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body_str = wp_remote_retrieve_body( $response );

        if ( $code === 200 ) {
            return [
                'success' => true,
                'message' => __( 'Connection successful!', 'wp-ai-edit' ),
            ];
        }

        $decoded = json_decode( $body_str, true );
        $error_msg = $decoded['error']['message'] ?? $body_str;

        return [
            'success' => false,
            'message' => sprintf(
                /* translators: 1: HTTP code, 2: error message */
                __( 'HTTP %1$d: %2$s', 'wp-ai-edit' ),
                $code,
                $error_msg
            ),
        ];
    }

    // ── Encryption helpers ──

    public static function encrypt_api_key( string $plain_key ): ?string {
        $secret = self::get_encryption_key();
        $nonce  = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
        $cipher = sodium_crypto_secretbox( $plain_key, $nonce, $secret );
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        return base64_encode( $nonce . $cipher );
    }

    public static function decrypt_api_key( string $encrypted ): ?string {
        $secret  = self::get_encryption_key();
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
        $decoded = base64_decode( $encrypted, true );

        if ( $decoded === false || strlen( $decoded ) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES ) {
            return null;
        }

        $nonce  = substr( $decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
        $cipher = substr( $decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
        $plain  = sodium_crypto_secretbox_open( $cipher, $nonce, $secret );

        return $plain === false ? null : $plain;
    }

    private static function get_encryption_key(): string {
        if ( defined( 'WP_AI_EDIT_ENCRYPTION_KEY' ) ) {
            // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
            return base64_decode( WP_AI_EDIT_ENCRYPTION_KEY );
        }

        $stored = get_option( 'wp_ai_edit_enc_key' );
        if ( is_string( $stored ) && $stored !== '' ) {
            // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
            return base64_decode( $stored );
        }

        $key = sodium_crypto_secretbox_keygen();
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        update_option( 'wp_ai_edit_enc_key', base64_encode( $key ), false );
        return $key;
    }

    // ── SSE helpers ──

    public static function init_sse_headers(): void {
        // Prevent any output buffering.
        while ( ob_get_level() > 0 ) {
            ob_end_clean();
        }

        header( 'Content-Type: text/event-stream; charset=utf-8' );
        header( 'Cache-Control: no-cache' );
        header( 'Connection: keep-alive' );
        header( 'X-Accel-Buffering: no' );
    }

    public static function send_sse_error( string $message ): void {
        $data = wp_json_encode( [ 'error' => $message ] );
        echo "data: {$data}\n\n";
        self::flush_output();
        echo "data: [DONE]\n\n";
        self::flush_output();
    }

    private static function flush_output(): void {
        if ( ob_get_level() > 0 ) {
            ob_flush();
        }
        flush();
    }
}
