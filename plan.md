# WP AI Edit 구현 계획 및 검증 문서

## 1. 목표

WordPress 6.9.4 + PHP 8.3 환경에서 동작하는 독립 플러그인 WP AI Edit를 구현한다.
이 플러그인은 Gutenberg 에디터에 AI 편집 기능을 붙이고, Azure OpenAI API를 연결해 다음 기능을 제공한다.

- Your Command
- Write More
- My Style (기존 Improve 대체)
- Highlight
- Summarize
- Write Analogy
- Fix Grammar
- SEO Helper

추가 요구사항:

- API Endpoint / API Key / Model을 관리자 설정에서 직접 입력
- 지원 모델: `gpt-5.4-pro`, `gpt-5.4-mini`, `gpt-5.4-nano`, `gpt-5.4`
- 각 액션별 시스템 프롬프트를 관리자 화면에서 수정 가능
- 생성 결과에 대해 `Use`, `Regenerate`, `Write More`, `Close` 선택지 제공
- Azure OpenAI API의 스트리밍 응답을 실시간으로 표시
- Tail Prompt를 통해 AI 응답을 Gutenberg 호환 HTML 포맷으로 수신
- 각 프롬프트 편집 영역에 전체 프롬프트(시스템 프롬프트 + Tail Prompt) 미리보기 제공

## 2. 구현 범위

### 관리자 기능

- 최상위 관리자 메뉴 `WP AI Edit` 추가
- Azure OpenAI API 연결 설정
  - Endpoint URL
  - API Key
  - Model 선택
- 연결 테스트 버튼 제공
- 각 액션별 시스템 프롬프트 편집 제공
- 각 프롬프트 필드에 전체 프롬프트 미리보기 (펼치기/접기) 제공
- Tail Prompt 편집 제공 (Gutenberg 호환 HTML 응답 포맷 지시)
- API Key는 libsodium 기반으로 암호화 저장

### Gutenberg 기능

- 텍스트 블록 선택 시 인라인 툴바에 AI 버튼 노출
- AI 액션 팝오버 제공
- `Your Command` 자유 입력 UI 제공
- 스트리밍 응답 결과 팝오버 제공
- 결과에 대해 다음 액션 제공
  - Use
  - Regenerate
  - Write More
  - Close

### REST API

- `POST /wp-json/wp-ai-edit/v1/generate`
- `POST /wp-json/wp-ai-edit/v1/test-connection`
- `GET /wp-json/wp-ai-edit/v1/prompts`

## 3. 구현 구조

```text
wp-ai-edit/
├── wp-ai-edit.php
├── package.json
├── plan.md
├── assets/
│   ├── admin.css
│   └── admin.js
├── build/
│   ├── index.asset.php
│   ├── index.css
│   ├── index-rtl.css
│   └── index.js
├── includes/
│   ├── class-admin-settings.php
│   ├── class-openai-client.php
│   ├── class-plugin.php
│   ├── class-prompt-manager.php
│   └── class-rest-api.php
└── src/
    ├── index.js
    ├── editor/
    │   ├── action-popover.js
    │   ├── command-input.js
    │   ├── plugin.js
    │   ├── result-popover.js
    │   ├── streaming-handler.js
    │   └── toolbar-button.js
    ├── store/
    │   └── index.js
    └── styles/
        └── editor.scss
```

## 4. 핵심 설계 결정

### 4.1 Azure OpenAI 연동

Azure OpenAI의 `/chat/completions` 엔드포인트를 사용한다.
플러그인은 저장된 설정값 또는 관리자 화면의 현재 입력값을 기준으로 연결 테스트를 수행한다.

### 4.2 스트리밍 방식

- PHP: cURL로 Azure OpenAI 스트리밍 응답 수신
- PHP -> 브라우저: `text/event-stream`으로 SSE 전달
- JS: `fetch` + `ReadableStream`으로 chunk 수신

### 4.3 Gutenberg 통합 방식

- `editor.BlockEdit` 필터로 기존 텍스트 블록에 제어 UI 추가
- 인라인 툴바 그룹에 AI 버튼 노출
- 지원 블록별 수정 가능한 속성 자동 매핑
  - `core/paragraph` -> `content`
  - `core/heading` -> `content`
  - `core/list-item` -> `content`
  - `core/list` -> `values`
  - `core/quote` -> `value`

### 4.4 보안

- API Key 암호화 저장: `sodium_crypto_secretbox`
- 관리자 기능: `manage_options`
- 에디터 기능: `edit_posts`
- REST API는 WordPress REST nonce 기반 요청 처리

## 5. 요구사항 대비 구현 결과

### 5.1 관리자 설정

구현 완료.

- Endpoint 입력 지원
- API Key 입력 및 암호화 저장 지원
- 모델 4종 선택 지원
- 연결 테스트 지원
- 시스템 프롬프트 액션별 편집 지원
- Tail Prompt 편집 지원 (Gutenberg HTML 응답 포맷)
- 각 프롬프트별 전체 프롬프트(시스템 + Tail) 미리보기 제공

### 5.2 에디터 액션

구현 완료.

- Your Command
- Write More
- My Style (기존 Improve 대체)
- Highlight
- Summarize
- Write Analogy
- Fix Grammar
- SEO Helper

### 5.3 생성 결과 후속 액션

구현 완료.

- Use
- Regenerate
- Write More
- Close

### 5.4 스트리밍 응답

구현 완료.

- 서버에서 SSE로 전달
- 클라이언트에서 실시간 표시
- 완료 시 버튼 활성화

### 5.5 시스템 프롬프트 커스터마이징

구현 완료.

각 기능별 프롬프트를 WordPress 관리자 설정에서 편집 가능하도록 구현했다.

### 5.6 Tail Prompt

구현 완료.

- 모든 시스템 프롬프트에 자동 부착 (SEO Helper 제외)
- AI 응답을 Gutenberg 호환 HTML(<p>, <strong>, <mark>, <ul> 등)으로 수신
- 관리자 설정 최하단에서 편집 가능

### 5.7 프롬프트 미리보기

구현 완료.

- 각 프롬프트 필드 하단에 “View Full Prompt” 펼치기 버튼 제공
- 시스템 프롬프트 + Tail Prompt 합쳐진 전체 프롬프트 확인 가능
- 프롬프트 또는 Tail Prompt 수정 시 실시간 업데이트

## 6. 구현 중 보정한 항목

초기 구현 후 실제 사용 관점에서 다음 항목을 보정했다.

1. `Write More` 버튼이 원래 블록 텍스트가 아니라 직전에 생성된 결과 텍스트를 기반으로 다시 생성되도록 수정
2. 연결 테스트 버튼이 저장된 값만 보지 않고, 관리자 화면에 현재 입력한 Endpoint / API Key / Model 값을 사용하도록 수정
3. 블록 종류별로 올바른 속성(`content`, `value`, `values`)에 결과를 반영하도록 수정
4. AI 버튼을 블록 보조 툴바가 아니라 인라인 툴바 그룹으로 이동해 이미지와 더 유사한 UX로 보정
5. 전체 JS 코드 포맷 및 린트 오류 제거
6. Improve 액션을 My Style로 변경하여 사용자 문체 스타일 변환 기능으로 바꾼
7. Highlight 액션 추가 — 핵심 키워드에 <mark>, <strong> 태그 적용
8. SEO Helper 액션 추가 — 전체 블록 텍스트 수집 후 제목/키워드/요약 생성 (Tail Prompt 미적용)
9. Tail Prompt 시스템 추가 — AI 응답을 Gutenberg HTML 포맷으로 수신
10. 각 프롬프트 필드에 전체 프롬프트(시스템 + Tail) 미리보기 기능 추가
11. ResultPopover에서 HTML 콘텐츠 렌더링 지원 (dangerouslySetInnerHTML)
12. SEO Helper 결과는 Use/Write More 버튼 숨기고 Regenerate/Close만 표시

## 7. 검증 결과

현재 워크스페이스에서 가능한 범위까지 검증 완료.

### 7.1 정적 검증

성공.

- `npm run lint:js` 통과
- `npm run build` 성공
- `php -l wp-ai-edit.php includes/*.php` 전체 통과
- VS Code Problems 기준 오류 없음

### 7.2 기능 요구사항 코드 점검

성공.

- 관리자 설정 화면 코드 존재
- REST API 라우트 존재
- Azure OpenAI 스트리밍 처리 코드 존재
- 시스템 프롬프트 관리 코드 존재
- 8개 AI 액션 UI 존재
- 결과 선택지 UI 존재
- Tail Prompt 관리 코드 존재
- 프롬프트 미리보기 UI 존재

### 7.3 실제 런타임 검증 범위

부분 검증.

현재 워크스페이스에는 WordPress 6.9.4 실행 환경, 관리자 로그인 세션, 실제 Azure OpenAI 자격 증명이 없어서 아래 항목은 코드 수준으로만 확인했다.

- WordPress 관리자 메뉴 실화면 렌더링
- Gutenberg 편집기에서 실제 버튼 노출 위치 확인
- 실제 Azure OpenAI 호출 성공 여부
- 스트리밍 응답의 실제 브라우저 표시
- 생성 결과를 블록에 적용하는 실동작

즉, 정적 검증과 코드 경로 검증은 완료되었지만, 실제 WordPress 런타임에서의 E2E 확인은 아직 남아 있다.

## 8. 현재 판단

현재 상태는 다음과 같이 판단한다.

- 구현 자체는 요구사항 범위에 맞게 완료됨
- 정적 검증 기준으로는 정상 상태
- 실제 WordPress 6.9.4 환경에서 설치 후 관리자/에디터 동작을 확인하는 마지막 런타임 검증이 필요함

## 9. 권장 런타임 검증 절차

1. WordPress 6.9.4 + PHP 8.3 환경에 플러그인 폴더를 설치한다.
2. 관리자 메뉴 `WP AI Edit`에서 Endpoint / API Key / Model을 입력한다.
3. `Test Connection`으로 Azure OpenAI 연결을 확인한다.
4. Gutenberg에서 문단 블록을 선택한다.
5. 인라인 툴바의 AI 버튼이 표시되는지 확인한다.
6. 각 액션을 실행해 스트리밍 응답이 보이는지 확인한다.
7. `Use`, `Regenerate`, `Write More`, `Close`가 기대대로 동작하는지 확인한다.
8. 설정 화면에서 시스템 프롬프트를 수정한 후 결과가 바뀌는지 확인한다.

## 10. 현재 결론

코드 기준으로는 요구사항이 반영되어 있으며, 정적 검증도 모두 통과했다.
다만 실제 WordPress 6.9.4 인스턴스와 Azure OpenAI 자격 증명으로 수행하는 런타임 E2E 검증은 이 워크스페이스 안에서는 수행할 수 없었다.
