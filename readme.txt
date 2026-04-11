=== Hemtory AI Editor ===
Contributors: your-wp-org-username
Tags: ai, content, editor, gutenberg, openai, anthropic, claude
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 8.3
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hemtory AI Editor – Bring the power of AI to your Gutenberg editor with your own API key. No SaaS subscription needed.

== Description ==

Hemtory AI Editor lets you supercharge the WordPress Gutenberg editor with AI — using your own API key. No monthly SaaS fees, no vendor lock-in. Just connect your Azure OpenAI, OpenAI, or Anthropic key, choose the model that fits your budget, and start creating content your way.

Every prompt is fully customizable, so the AI writes in your voice and your style. You control the cost, the quality, and the output — all from inside the editor you already know.

**AI Editing Actions:**

* **Your Command** – Give free-text instructions to the AI
* **Write More** – Continue writing with the same tone and style
* **My Style** – Rewrite text to match your personal writing style defined in the prompt
* **Highlight** – Mark key phrases and keywords with strong and em tags
* **Summarize** – Condense long text into key points
* **Write Analogy** – Rewrite using metaphors and analogies
* **Fix Grammar** – Correct grammar, spelling, and punctuation
* **SEO Helper** – Analyze entire document to generate 3 recommended titles, 5 core keywords, and a 100-character summary

**AI Image Analysis:**

* **Describe Image** – Analyze and describe visual elements, composition, colors, and mood of an image
* **Suggest Caption** – Generate a concise, engaging caption for blog posts and articles
* **Your Command** – Give free-text instructions about any selected image
* Results are inserted as a new paragraph block below the image
* Vision support: OpenAI/Azure (image_url) and Anthropic (base64 encoding)

**Platform Features:**

* Bring your own API key – use Azure OpenAI, OpenAI, or Anthropic with no middleman
* Choose models by budget – from cost-effective Nano/Haiku to powerful Pro/Opus, with pricing displayed per model
* Real-time streaming AI responses via SSE
* Gutenberg-compatible HTML output via configurable Tail Prompt
* Fully customizable system prompts per action with live preview
* Works with single blocks, multiple block selection, and image blocks
* API key encryption using libsodium
* Separate provider tabs with independent endpoint, API key, model, and pricing display
* Korean (ko_KR) translation included

**Why Your Own API Key?**

* **Cost control** – Pay only for what you use, at the provider's direct pricing
* **Model freedom** – Switch between GPT-5.4 Nano ($0.20/MTok) and GPT-5.4 Pro ($30/MTok), or Claude Haiku and Opus, depending on your needs
* **Privacy** – Your content goes directly to the API provider, not through a third-party service
* **No subscription** – No recurring plugin fees, no usage caps

**Requirements:**

* Azure OpenAI API endpoint and key, OpenAI API key, or Anthropic API key (user-provided)

---

**한국어 설명:**

Hemtory AI Editor는 자신이 보유한 AI API 키 하나로 WordPress Gutenberg 에디터에 AI의 힘을 불어넣는 플러그인입니다. 별도의 SaaS 구독이나 중간 서비스 없이, 내 API 키를 직접 연결하여 비용 효율적으로 나만의 콘텐츠를 만들어 나갈 수 있습니다.

**주요 특징:**

* 내 API 키 직접 연결 – Azure OpenAI, OpenAI, Anthropic 지원
* 예산에 맞는 모델 선택 – 저비용 Nano/Haiku부터 고성능 Pro/Opus까지, 모델별 가격 표시
* AI 글쓰기 8가지 액션 – 자유 명령, 이어쓰기, 내 스타일, 하이라이트, 요약, 비유로 쓰기, 문법 교정, SEO 도우미
* AI 이미지 분석 – 이미지 설명, 캡션 제안, 자유 명령으로 이미지 해석 후 하단에 새 단락 삽입
* 프롬프트 완전 커스터마이징 – 액션별 시스템 프롬프트 편집 및 미리보기
* 실시간 스트리밍 응답(SSE) – AI 결과를 실시간으로 확인
* 멀티 블록 선택 지원 – 여러 블록을 동시에 선택하여 AI 처리
* API 키 암호화(libsodium) – 안전한 키 저장
* 비용은 사용한 만큼만 – 구독료 없음, 사용 제한 없음

== Installation ==

1. Download `hemtory-ai-editor.zip` from the `releases/` directory
2. In WordPress admin, go to **Plugins → Add New → Upload Plugin** and upload the ZIP file
3. Activate the plugin
4. Go to **Hemtory AI Editor** in the admin menu
5. Use the LLM tabs to save settings for Azure OpenAI, OpenAI, or Anthropic
6. Click **Test Connection** to verify
7. Start editing with AI in the Gutenberg editor

**Reinstalling:** To reinstall or update, first **deactivate** the plugin and then **delete** it from the Plugins page. After that, upload and install the new ZIP file.

== Frequently Asked Questions ==

= What API do I need? =
You can use Azure OpenAI, OpenAI, or Anthropic. Azure OpenAI requires an endpoint and key. OpenAI and Anthropic require an API key only.

= Is my API key secure? =
Yes. API keys are encrypted using libsodium before storage.

= What is the Tail Prompt? =
The Tail Prompt is automatically appended to every system prompt (except SEO Helper) to instruct the AI to respond in Gutenberg-compatible HTML format. You can customize it in the admin settings.

= What does SEO Helper do? =
SEO Helper collects text from all blocks in the editor and asks the AI to generate 3 recommended titles, 5 core keywords, and a 100-character summary for the entire document.

= Can I preview the full prompt sent to the AI? =
Yes. Each prompt field in the admin settings has a "View Full Prompt" toggle that shows the system prompt combined with the Tail Prompt.

= Does it support image analysis? =
Yes. Select an image block and click the AI button to describe the image, suggest a caption, or give a custom command. The result is inserted as a new paragraph below the image. Works with OpenAI/Azure (image_url) and Anthropic (base64).

= Do I need a paid subscription for this plugin? =
No. The plugin itself is free. You only pay for the API usage directly to your chosen provider (Azure OpenAI, OpenAI, or Anthropic).

== Changelog ==

= 1.3.0 =
* Renamed plugin to Hemtory AI Editor
* Added AI image analysis: Describe Image, Suggest Caption, and custom image commands
* Image analysis results are inserted as a new paragraph below the image block
* Vision support for OpenAI/Azure (image_url) and Anthropic (base64)

= 1.2.0 =
* Added Anthropic (Claude) API support with Claude Opus 4.6, Sonnet 4.6, and Haiku 4.5 models
* Per-provider model tabs with pricing display (input/output per MTok)
* Azure OpenAI endpoint auto-appends /openai/v1 when entering a base resource URL
* Provider tabs now show logo icons (Azure, OpenAI, Anthropic)
* GPT-5.4 Pro selection prompts a cost confirmation dialog
* Admin settings redesigned with card layout and improved spacing
* Each provider saves its own endpoint, API key, and model independently

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