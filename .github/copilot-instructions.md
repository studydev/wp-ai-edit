# Hemtory AI Editor - Project Guidelines

## Overview

WordPress Gutenberg 블록 에디터용 AI 콘텐츠 편집 플러그인. Azure OpenAI, OpenAI, Anthropic API 지원.

## Tech Stack

- **PHP 8.3+**, WordPress 6.4+
- **Frontend**: `@wordpress/scripts` (React/JSX), SCSS
- **Build**: `npm run build` → `build/` 디렉토리 생성

## Build & Package

변경 사항이 발생하면 배포용 zip 파일을 빌드한다.

### 빌드 절차

1. JS/CSS 변경 시: `npm run build` 실행
2. zip 패키징:
   ```bash
   mkdir -p releases && zip -r releases/hemtory-ai-editor.zip \
     wp-ai-edit.php \
     readme.txt \
     LICENSE \
     includes/ \
     build/ \
     assets/ \
     languages/ \
     -x "*.DS_Store" -x "__MACOSX/*"
   ```
3. `hemtory-ai-editor.zip`은 `releases/` 디렉토리에 생성한다.

### 패키지 포함 대상

- `wp-ai-edit.php` (메인 플러그인 파일)
- `readme.txt`, `LICENSE`
- `includes/` (PHP 클래스)
- `build/` (컴파일된 JS/CSS)
- `assets/` (어드민 CSS/JS)
- `languages/` (번역 파일)

### 패키지 제외 대상

- `src/`, `node_modules/`, `package.json`, `package-lock.json`
- `.github/`, `.git/`
- `*.afphoto`, `plan.md`, `CODE_OF_CONDUCT.md`, `README.md`

## Git & GitHub

요청된 변경을 완료한 후, GitHub 저장소에 반영한다.

- 원격 저장소: `studydev/wp-ai-edit` (branch: `main`)
- 커밋 메시지는 변경 내용을 간결하게 영문으로 작성
- push 전 반드시 사용자 확인을 받는다

## Conventions

- PHP: `declare(strict_types=1)`, namespace `WpAiEdit`
- 들여쓰기: 탭 (PHP), 탭 (JS/SCSS)
- WordPress 코딩 표준 준수
- 프롬프트 기본값에 HTML 태그를 직접 사용하지 않는다 (에디터에서 태그가 렌더링되어 보이지 않는 문제 방지)
