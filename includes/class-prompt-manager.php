<?php

declare(strict_types=1);

namespace WpAiEdit;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PromptManager {

    private const OPTION_KEY      = 'wp_ai_edit_prompts';
    private const TAIL_OPTION_KEY = 'wp_ai_edit_tail_prompt';

    /** Actions that do NOT receive the tail prompt. */
    private const NO_TAIL_ACTIONS = [ 'seo_helper', 'describe_image', 'suggest_caption', 'image_command' ];

    /** Actions that are image-based (vision). */
    private const IMAGE_ACTIONS = [ 'describe_image', 'suggest_caption', 'image_command' ];

    /** @var array<string, string> */
    private static array $defaults = [
        'command'    => 'You are a helpful content writing assistant. Follow the user\'s instruction exactly. Respond only with the resulting text, without any additional explanation.',
        'write_more' => 'You are a content writer. Continue writing the following text naturally, maintaining the same tone, style, and language. Write 2-3 additional paragraphs. Respond only with the new continuation text.',
        'my_style'   => 'You are a writing style transformer. Rewrite the following text to match the author\'s personal writing style as described below. Preserve the original meaning while adapting the expression. Maintain the same language. Respond only with the rewritten text.',
        'highlight'  => 'You are a content highlighter. Analyze the following text and identify the most important key phrases, keywords, and significant terms. Return the original text with critical terms wrapped in bold (strong) tags and secondary key phrases wrapped in emphasis (em) tags. Preserve the original text structure, meaning, and language.',
        'summarize'  => 'You are a skilled summarizer. Summarize the following text concisely, capturing the key points in a brief paragraph. Maintain the same language as the original text. Respond only with the summary.',
        'analogy'    => 'You are a creative writer. Rewrite the following text using vivid metaphors and analogies to make the concepts more relatable and engaging. Maintain the same language as the original. Respond only with the rewritten text.',
        'grammar'    => 'You are a grammar expert. Fix all grammar, spelling, and punctuation errors in the following text. Preserve the original meaning, style, and language. Respond only with the corrected text.',
        'seo_helper' => 'You are an SEO analysis expert. Analyze the entire document content provided below and return the following in the same language as the document:

1. Three SEO-optimized recommended titles for this content.
2. Five core keywords or key phrases extracted from the content.
3. A concise document summary within 100 characters.

Format your response as structured HTML using h3 headings for each section, ol (ordered list) for titles, ul (unordered list) for keywords, and p (paragraph) for the summary. Do not include any other commentary.',
        'describe_image'  => 'You are an image analysis expert. Describe the provided image in detail, including its visual elements, composition, colors, mood, and any text visible in the image. Write your description as a natural, flowing paragraph. Use the same language as the blog or website content surrounding this image. Respond only with the description text, without any additional commentary or formatting.',
        'suggest_caption' => 'You are a content editor. Look at the provided image and write a concise, engaging caption suitable for a blog post or article. The caption should describe what the image shows in one or two sentences. Use the same language as the blog or website content surrounding this image. Respond only with the caption text.',
        'image_command'   => 'You are a helpful assistant that can analyze images. Follow the user\'s instruction about the provided image exactly. Respond only with the resulting text, without any additional explanation or formatting.',
    ];

    private static string $default_tail_prompt = 'IMPORTANT — Response format rules:
- Your response MUST be valid HTML compatible with the WordPress Gutenberg block editor.
- Do NOT use Markdown formatting.
- Do NOT include Gutenberg block comments (such as wp:paragraph markers).
- Return ONLY the inner HTML content — no wrapper elements, no explanations.';

    /**
     * @return array<string, string>
     */
    public static function get_defaults(): array {
        return self::$defaults;
    }

    /**
     * @return array<string, string>
     */
    public static function get_action_labels(): array {
        return [
            'command'         => __( 'Your Command', 'wp-ai-edit' ),
            'write_more'      => __( 'Write More', 'wp-ai-edit' ),
            'my_style'        => __( 'My Style', 'wp-ai-edit' ),
            'highlight'       => __( 'Highlight', 'wp-ai-edit' ),
            'summarize'       => __( 'Summarize', 'wp-ai-edit' ),
            'analogy'         => __( 'Write Analogy', 'wp-ai-edit' ),
            'grammar'         => __( 'Fix Grammar', 'wp-ai-edit' ),
            'seo_helper'      => __( 'SEO Helper', 'wp-ai-edit' ),
            'describe_image'  => __( 'Describe Image', 'wp-ai-edit' ),
            'suggest_caption' => __( 'Suggest Caption', 'wp-ai-edit' ),
            'image_command'   => __( 'Image Command', 'wp-ai-edit' ),
        ];
    }

    // ── Tail Prompt ──

    public static function get_default_tail_prompt(): string {
        return self::$default_tail_prompt;
    }

    public static function get_tail_prompt(): string {
        $saved = get_option( self::TAIL_OPTION_KEY, '' );
        return ( is_string( $saved ) && $saved !== '' ) ? $saved : self::$default_tail_prompt;
    }

    public static function save_tail_prompt( string $prompt ): bool {
        return update_option( self::TAIL_OPTION_KEY, sanitize_textarea_field( $prompt ) );
    }

    /**
     * @return string[]
     */
    public static function get_no_tail_actions(): array {
        return self::NO_TAIL_ACTIONS;
    }

    // ── Prompt CRUD ──

    /**
     * @return array<string, string>
     */
    public static function get_all(): array {
        $saved = get_option( self::OPTION_KEY, [] );
        if ( ! is_array( $saved ) ) {
            $saved = [];
        }
        return array_merge( self::$defaults, $saved );
    }

    public static function get( string $action ): string {
        $all = self::get_all();
        return $all[ $action ] ?? ( self::$defaults[ $action ] ?? '' );
    }

    /**
     * Return the full system prompt including the tail prompt (if applicable).
     */
    public static function get_full( string $action ): string {
        $prompt = self::get( $action );
        if ( ! in_array( $action, self::NO_TAIL_ACTIONS, true ) ) {
            $tail = self::get_tail_prompt();
            if ( $tail !== '' ) {
                $prompt .= "\n\n" . $tail;
            }
        }
        return $prompt;
    }

    /**
     * @param array<string, string> $prompts
     */
    public static function save( array $prompts ): bool {
        $sanitized = [];
        foreach ( $prompts as $key => $value ) {
            if ( array_key_exists( $key, self::$defaults ) ) {
                $sanitized[ $key ] = sanitize_textarea_field( $value );
            }
        }
        return update_option( self::OPTION_KEY, $sanitized );
    }

    public static function reset( string $action ): bool {
        $current = get_option( self::OPTION_KEY, [] );
        if ( ! is_array( $current ) ) {
            $current = [];
        }
        unset( $current[ $action ] );
        return update_option( self::OPTION_KEY, $current );
    }

    public static function reset_all(): bool {
        return delete_option( self::OPTION_KEY );
    }

    /**
     * @return array{role: string, content: string}[]
     */
    public static function build_messages( string $action, string $text, string $command = '' ): array {
        $system_prompt = self::get_full( $action );

        $messages = [
            [ 'role' => 'system', 'content' => $system_prompt ],
        ];

        if ( $action === 'command' && $command !== '' ) {
            $messages[] = [
                'role'    => 'user',
                'content' => "Instruction: {$command}\n\nText:\n{$text}",
            ];
        } else {
            $messages[] = [
                'role'    => 'user',
                'content' => $text,
            ];
        }

        return $messages;
    }

    /**
     * Build messages for vision (image analysis) actions.
     *
     * The returned messages contain the system prompt and a user text prompt.
     * The image content is injected later by OpenAIClient based on the provider format.
     *
     * @return array{role: string, content: string}[]
     */
    public static function build_vision_messages( string $action, string $command = '' ): array {
        $system_prompt = self::get_full( $action );

        $messages = [
            [ 'role' => 'system', 'content' => $system_prompt ],
        ];

        if ( $action === 'image_command' && $command !== '' ) {
            $user_text = $command;
        } else {
            $user_text = self::get_image_user_prompt( $action );
        }

        $messages[] = [
            'role'    => 'user',
            'content' => $user_text,
        ];

        return $messages;
    }

    /**
     * @return string[]
     */
    public static function get_image_actions(): array {
        return self::IMAGE_ACTIONS;
    }

    private static function get_image_user_prompt( string $action ): string {
        return match ( $action ) {
            'describe_image'  => 'Please describe this image in detail.',
            'suggest_caption' => 'Please suggest a caption for this image.',
            default           => 'Analyze this image.',
        };
    }
}
