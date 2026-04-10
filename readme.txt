=== WP AI Edit ===
Contributors: your-wp-org-username
Tags: ai, content, editor, gutenberg, openai
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 8.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered content editing for the WordPress Gutenberg editor.

== Description ==

WP AI Edit adds AI-powered editing capabilities directly into the Gutenberg block editor toolbar.

**AI Editing Actions:**

* **Your Command** – Give free-text instructions to the AI
* **Write More** – Continue writing with the same tone and style
* **My Style** – Rewrite text to match your personal writing style defined in the prompt
* **Highlight** – Mark key phrases and keywords with <mark> and <strong> tags
* **Summarize** – Condense long text into key points
* **Write Analogy** – Rewrite using metaphors and analogies
* **Fix Grammar** – Correct grammar, spelling, and punctuation
* **SEO Helper** – Analyze entire document to generate 3 recommended titles, 5 core keywords, and a 100-character summary

**Platform Features:**

* Real-time streaming AI responses (SSE)
* Gutenberg-compatible HTML output via configurable Tail Prompt
* Customizable system prompts per action with full prompt preview
* Tail Prompt appended to all actions (except SEO Helper) for consistent HTML formatting
* Works with single and multiple block selection
* API key encryption using libsodium

**Requirements:**

* Azure OpenAI API endpoint and key (user-provided)

== Installation ==

1. Upload the plugin to `/wp-content/plugins/wp-ai-edit/`
2. Activate the plugin
3. Go to **WP AI Edit** in the admin menu
4. Enter your Azure OpenAI API endpoint, key, and select a model
5. Click **Test Connection** to verify
6. Start editing with AI in the Gutenberg editor

== Frequently Asked Questions ==

= What API do I need? =
You need an Azure OpenAI API endpoint and key.

= Is my API key secure? =
Yes. API keys are encrypted using libsodium before storage.

= What is the Tail Prompt? =
The Tail Prompt is automatically appended to every system prompt (except SEO Helper) to instruct the AI to respond in Gutenberg-compatible HTML format. You can customize it in the admin settings.

= What does SEO Helper do? =
SEO Helper collects text from all blocks in the editor and asks the AI to generate 3 recommended titles, 5 core keywords, and a 100-character summary for the entire document.

= Can I preview the full prompt sent to the AI? =
Yes. Each prompt field in the admin settings has a "View Full Prompt" toggle that shows the system prompt combined with the Tail Prompt.

== Changelog ==

= 1.1.0 =
* Added My Style action (replaced Improve) – rewrite text in your personal style
* Added Highlight action – mark key phrases with HTML tags
* Added SEO Helper action – full-document SEO analysis
* Added Tail Prompt system – configurable response format instruction
* AI responses now return Gutenberg-compatible HTML
* Added full prompt preview with expand/collapse in admin settings
* Result popover now renders HTML content with styling

= 1.0.0 =
* Initial release

== Screenshots ==

1. AI toolbar button in the Gutenberg editor
2. Action menu with eight AI editing options
3. Real-time streaming AI response with HTML rendering
4. Admin settings page with prompt preview
5. Tail Prompt configuration section
6. SEO Helper analysis result