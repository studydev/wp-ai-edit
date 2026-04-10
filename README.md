# WP AI Edit

**AI-powered content editing for the WordPress Gutenberg editor.**

Gutenberg 블록 에디터에 AI 편집 기능을 추가하는 WordPress 플러그인입니다. Azure OpenAI API를 연동하여 글쓰기, 교정, 요약, SEO 분석 등을 에디터 안에서 바로 수행합니다.

A WordPress plugin that adds AI editing capabilities directly into the Gutenberg block editor toolbar. Powered by Azure OpenAI API for writing, proofreading, summarizing, SEO analysis, and more — all within the editor.

---

## 기능 / Features

| 액션 | Action | 설명 / Description |
|------|--------|--------------------|
| 자유 명령 | Your Command | 자유 텍스트 지시로 AI에 명령 / Give free-text instructions to the AI |
| 이어쓰기 | Write More | 같은 톤과 스타일로 글 이어쓰기 / Continue writing with the same tone and style |
| 내 스타일 | My Style | 프롬프트에 정의한 개인 문체로 변환 / Rewrite text to match your personal writing style |
| 하이라이트 | Highlight | 핵심 구문에 `<strong>`, `<em>` 태그 적용 / Mark key phrases with HTML emphasis tags |
| 요약 | Summarize | 긴 텍스트를 핵심 요점으로 압축 / Condense long text into key points |
| 비유로 쓰기 | Write Analogy | 비유와 은유로 텍스트 재구성 / Rewrite using metaphors and analogies |
| 문법 교정 | Fix Grammar | 문법, 맞춤법, 구두점 수정 / Correct grammar, spelling, and punctuation |
| SEO 도우미 | SEO Helper | 전체 문서 분석 → 제목 3개, 키워드 5개, 100자 요약 / Analyze entire document for titles, keywords, and summary |

### 플랫폼 기능 / Platform Features

- **실시간 스트리밍** — SSE 기반 AI 응답 실시간 표시 / Real-time streaming AI responses via SSE
- **Gutenberg HTML 출력** — Tail Prompt로 에디터 호환 HTML 포맷 보장 / Gutenberg-compatible HTML output via configurable Tail Prompt
- **프롬프트 커스터마이징** — 액션별 시스템 프롬프트 편집 + 전체 프롬프트 미리보기 / Customizable system prompts per action with full prompt preview
- **멀티 블록 선택** — 여러 블록을 동시에 선택하여 AI 처리 / Works with single and multiple block selection
- **API 키 암호화** — libsodium 기반 암호화 저장 / API key encryption using libsodium
- **보안** — DOMPurify XSS 방지, SSRF 엔드포인트 검증, Rate Limit / DOMPurify XSS protection, SSRF endpoint validation, rate limiting
- **다국어 지원** — 한국어(ko_KR) 번역 포함 / Korean (ko_KR) translation included

---

## 요구 사항 / Requirements

- WordPress 6.4+
- PHP 8.3+
- Azure OpenAI API endpoint and key

---

## 설치 / Installation

### 방법 1: ZIP 업로드 / ZIP Upload

1. [Releases](https://github.com/studydev/wp-ai-edit/releases) 페이지에서 `wp-ai-edit.zip`을 다운로드합니다.
2. WordPress 관리자 → **플러그인** → **새로 추가** → **플러그인 업로드**로 ZIP을 설치합니다.

   Download `wp-ai-edit.zip` from Releases, then upload via **Plugins → Add New → Upload Plugin**.

### 방법 2: 수동 설치 / Manual

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/studydev/wp-ai-edit.git
cd wp-ai-edit
npm install && npm run build
```

### 방법 3: 개발 환경 / Development

```bash
git clone https://github.com/studydev/wp-ai-edit.git
cd wp-ai-edit
npm install
npm run start   # watch mode
```

---

## 설정 / Configuration

1. 플러그인을 활성화합니다. / Activate the plugin.
2. 관리자 메뉴 **WP AI Edit**으로 이동합니다. / Go to **WP AI Edit** in the admin menu.
3. Azure OpenAI API **엔드포인트**, **API 키**, **모델**을 입력합니다. / Enter your Azure OpenAI **endpoint**, **API key**, and **model**.
4. **연결 테스트**를 클릭하여 확인합니다. / Click **Test Connection** to verify.
5. Gutenberg 에디터에서 텍스트 블록을 선택하면 AI 버튼이 표시됩니다. / Select a text block in the Gutenberg editor to see the AI button.

### 지원 모델 / Supported Models

- GPT-5.4 Pro
- GPT-5.4
- GPT-5.4 Mini
- GPT-5.4 Nano

---

## 프로젝트 구조 / Project Structure

```
wp-ai-edit/
├── wp-ai-edit.php              # 메인 플러그인 파일 / Main plugin file
├── includes/
│   ├── class-admin-settings.php  # 관리자 설정 페이지 / Admin settings page
│   ├── class-openai-client.php   # Azure OpenAI API 클라이언트 / API client
│   ├── class-plugin.php          # 플러그인 부트스트랩 / Plugin bootstrap
│   ├── class-prompt-manager.php  # 프롬프트 관리 / Prompt management
│   └── class-rest-api.php        # REST API 엔드포인트 / REST API endpoints
├── src/
│   ├── editor/
│   │   ├── plugin.js             # Gutenberg 통합 / Gutenberg integration
│   │   ├── action-popover.js     # AI 액션 메뉴 / Action menu
│   │   ├── result-popover.js     # 결과 표시 / Result display
│   │   ├── streaming-handler.js  # SSE 스트리밍 처리 / SSE streaming
│   │   └── command-input.js      # 자유 명령 입력 / Command input
│   ├── store/index.js            # Redux 스토어 / Redux store
│   └── styles/editor.scss        # 에디터 스타일 / Editor styles
├── assets/
│   ├── admin.css                 # 관리자 스타일 / Admin styles
│   └── admin.js                  # 관리자 스크립트 / Admin scripts
├── languages/                    # 번역 파일 / Translation files
├── build/                        # 빌드 결과물 / Build output
├── readme.txt                    # WordPress.org readme
└── plan.md                       # 구현 계획 문서 / Implementation plan
```

---

## 보안 / Security

- **API 키 암호화**: `sodium_crypto_secretbox`로 암호화 저장 / Encrypted with libsodium
- **XSS 방지**: DOMPurify로 AI 응답 HTML 새니타이즈 / AI response HTML sanitized with DOMPurify
- **SSRF 방지**: HTTPS 전용 + private IP 차단 / HTTPS-only endpoint with private IP blocking
- **Rate Limit**: 사용자당 5초 쿨다운 / 5-second per-user cooldown
- **입력 제한**: 텍스트 최대 50,000자 / Max 50,000 characters per request
- **권한 제어**: 관리자(`manage_options`) / 편집자(`edit_posts`) / Capability-based access control

---

## 라이선스 / License

이 프로젝트는 [GPL-2.0-or-later](LICENSE) 라이선스로 배포됩니다.

This project is licensed under the [GPL-2.0-or-later](LICENSE) license.

---

## 기여 / Contributing

기여를 환영합니다! [Code of Conduct](CODE_OF_CONDUCT.md)를 확인해 주세요.

Contributions are welcome! Please read the [Code of Conduct](CODE_OF_CONDUCT.md) first.

1. Fork this repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Commit your changes (`git commit -m 'Add your feature'`)
4. Push to the branch (`git push origin feature/your-feature`)
5. Open a Pull Request
