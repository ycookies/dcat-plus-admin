<!DOCTYPE html>
<html lang="zh-CN" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>微渔小助 - 垂钓文旅数字化解决方案</title>
    <style id="theme-vars">
/* Fishing Tourism & Culture — Design Tokens
   Brand: Deep forest green + warm sunset gold
   Tone: Natural, premium, serene, professional
*/

:root {
  /* === Surface & Background === */
  --fishing-background: #FAFAF7;
  --fishing-foreground: #1A2E1A;
  --fishing-card: #FFFFFF;
  --fishing-card-foreground: #1A2E1A;
  --fishing-popover: #FFFFFF;
  --fishing-popover-foreground: #1A2E1A;
  --fishing-muted: #F0EFE8;
  --fishing-muted-foreground: #5A6B5A;
  --fishing-border: #E5E4DB;
  --fishing-input: #E5E4DB;
  --fishing-ring: #2D5A3D;

  /* === Primary Brand (Forest Green) === */
  --fishing-primary: #2D5A3D;
  --fishing-primary-foreground: #FFFFFF;
  --fishing-primary-light: #3D7A52;
  --fishing-primary-dark: #1E3E2A;
  --fishing-primary-tint: #E8F0EA;

  /* === Accent (Warm Gold/Sunset) === */
  --fishing-accent: #C89556;
  --fishing-accent-foreground: #FFFFFF;
  --fishing-accent-light: #DAAF7A;
  --fishing-accent-tint: #F7EFE3;

  /* === State Colors === */
  --fishing-success: #3D7A52;
  --fishing-warning: #C89556;
  --fishing-error: #B54A3A;
  --fishing-info: #4A7A9B;

  /* === Radius Scale === */
  --fishing-radius-sm: 4px;
  --fishing-radius-md: 8px;
  --fishing-radius-lg: 16px;
  --fishing-radius-xl: 24px;

  /* === Typography === */
  --fishing-font-sans: "Inter", "Noto Sans SC", "PingFang SC", "Microsoft YaHei", system-ui, sans-serif;
  --fishing-font-serif: "Noto Serif SC", "Source Han Serif SC", "Songti SC", serif;

  /* === Spacing (8pt grid) === */
  --fishing-s-1: 4px;
  --fishing-s-2: 8px;
  --fishing-s-3: 12px;
  --fishing-s-4: 16px;
  --fishing-s-5: 24px;
  --fishing-s-6: 32px;
  --fishing-s-7: 48px;
  --fishing-s-8: 80px;
  --fishing-s-9: 120px;
}

/* Dark mode support */
.dark {
  --fishing-background: #0F1A12;
  --fishing-foreground: #E8F0EA;
  --fishing-card: #16281C;
  --fishing-card-foreground: #E8F0EA;
  --fishing-popover: #16281C;
  --fishing-popover-foreground: #E8F0EA;
  --fishing-muted: #1E3526;
  --fishing-muted-foreground: #8BA693;
  --fishing-border: #2A4232;
  --fishing-input: #2A4232;
  --fishing-ring: #4A8A62;
  --fishing-primary: #4A8A62;
  --fishing-primary-foreground: #0F1A12;
  --fishing-primary-tint: #1A3323;
  --fishing-accent: #DAAF7A;
  --fishing-accent-foreground: #0F1A12;
  --fishing-accent-tint: #2A241A;
}

/* @theme inline */
/* @theme inline {
  --color-background: var(--fishing-background);
  --color-foreground: var(--fishing-foreground);
  --color-card: var(--fishing-card);
  --color-card-foreground: var(--fishing-card-foreground);
  --color-popover: var(--fishing-popover);
  --color-popover-foreground: var(--fishing-popover-foreground);
  --color-primary: var(--fishing-primary);
  --color-primary-foreground: var(--fishing-primary-foreground);
  --color-muted: var(--fishing-muted);
  --color-muted-foreground: var(--fishing-muted-foreground);
  --color-border: var(--fishing-border);
  --color-input: var(--fishing-input);
  --color-ring: var(--fishing-ring);
  --radius-sm: var(--fishing-radius-sm);
  --radius-md: var(--fishing-radius-md);
  --radius-lg: var(--fishing-radius-lg);
}
*/

    </style>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4.3.1/dist/index.global.js"></script>
    <script src="https://unpkg.com/lucide@1.8.0/dist/umd/lucide.min.js"></script>
    <style type="text/tailwindcss">
  @theme inline {
    --color-background: var(--fishing-background);
    --color-foreground: var(--fishing-foreground);
    --color-card: var(--fishing-card);
    --color-card-foreground: var(--fishing-card-foreground);
    --color-popover: var(--fishing-popover);
    --color-popover-foreground: var(--fishing-popover-foreground);
    --color-primary: var(--fishing-primary);
    --color-primary-foreground: var(--fishing-primary-foreground);
    --color-muted: var(--fishing-muted);
    --color-muted-foreground: var(--fishing-muted-foreground);
    --color-border: var(--fishing-border);
    --color-input: var(--fishing-input);
    --color-ring: var(--fishing-ring);
    --color-accent: var(--fishing-accent);
    --color-accent-foreground: var(--fishing-accent-foreground);
    --radius-sm: var(--fishing-radius-sm);
    --radius-md: var(--fishing-radius-md);
    --radius-lg: var(--fishing-radius-lg);
    --radius-xl: var(--fishing-radius-xl);
  }
  @layer base {
    body { background: var(--fishing-background); color: var(--fishing-foreground); }
    td, th { @apply break-words; word-break: break-all; word-break: auto-phrase; }
    th { @apply whitespace-nowrap; }
  }
    </style>
    <style id="semantic-token-fallback">
      .bg-background { background-color: var(--fishing-background); }
      .text-background { color: var(--fishing-background); }
      .border-background { border-color: var(--fishing-background); }
      .ring-background { --tw-ring-color: var(--fishing-background); }
      .bg-foreground { background-color: var(--fishing-foreground); }
      .text-foreground { color: var(--fishing-foreground); }
      .border-foreground { border-color: var(--fishing-foreground); }
      .ring-foreground { --tw-ring-color: var(--fishing-foreground); }
      .bg-card { background-color: var(--fishing-card); }
      .text-card { color: var(--fishing-card); }
      .border-card { border-color: var(--fishing-card); }
      .ring-card { --tw-ring-color: var(--fishing-card); }
      .bg-card-foreground { background-color: var(--fishing-card-foreground); }
      .text-card-foreground { color: var(--fishing-card-foreground); }
      .border-card-foreground { border-color: var(--fishing-card-foreground); }
      .ring-card-foreground { --tw-ring-color: var(--fishing-card-foreground); }
      .bg-popover { background-color: var(--fishing-popover); }
      .text-popover { color: var(--fishing-popover); }
      .border-popover { border-color: var(--fishing-popover); }
      .ring-popover { --tw-ring-color: var(--fishing-popover); }
      .bg-popover-foreground { background-color: var(--fishing-popover-foreground); }
      .text-popover-foreground { color: var(--fishing-popover-foreground); }
      .border-popover-foreground { border-color: var(--fishing-popover-foreground); }
      .ring-popover-foreground { --tw-ring-color: var(--fishing-popover-foreground); }
      .bg-primary { background-color: var(--fishing-primary); }
      .text-primary { color: var(--fishing-primary); }
      .border-primary { border-color: var(--fishing-primary); }
      .ring-primary { --tw-ring-color: var(--fishing-primary); }
      .bg-primary-foreground { background-color: var(--fishing-primary-foreground); }
      .text-primary-foreground { color: var(--fishing-primary-foreground); }
      .border-primary-foreground { border-color: var(--fishing-primary-foreground); }
      .ring-primary-foreground { --tw-ring-color: var(--fishing-primary-foreground); }
      .bg-muted { background-color: var(--fishing-muted); }
      .text-muted { color: var(--fishing-muted); }
      .border-muted { border-color: var(--fishing-muted); }
      .ring-muted { --tw-ring-color: var(--fishing-muted); }
      .bg-muted-foreground { background-color: var(--fishing-muted-foreground); }
      .text-muted-foreground { color: var(--fishing-muted-foreground); }
      .border-muted-foreground { border-color: var(--fishing-muted-foreground); }
      .ring-muted-foreground { --tw-ring-color: var(--fishing-muted-foreground); }
      .bg-border { background-color: var(--fishing-border); }
      .text-border { color: var(--fishing-border); }
      .border-border { border-color: var(--fishing-border); }
      .ring-border { --tw-ring-color: var(--fishing-border); }
      .bg-input { background-color: var(--fishing-input); }
      .text-input { color: var(--fishing-input); }
      .border-input { border-color: var(--fishing-input); }
      .ring-input { --tw-ring-color: var(--fishing-input); }
      .bg-ring { background-color: var(--fishing-ring); }
      .text-ring { color: var(--fishing-ring); }
      .border-ring { border-color: var(--fishing-ring); }
      .ring-ring { --tw-ring-color: var(--fishing-ring); }
      .bg-accent { background-color: var(--fishing-accent); }
      .text-accent { color: var(--fishing-accent); }
      .border-accent { border-color: var(--fishing-accent); }
      .ring-accent { --tw-ring-color: var(--fishing-accent); }
      .bg-accent-foreground { background-color: var(--fishing-accent-foreground); }
      .text-accent-foreground { color: var(--fishing-accent-foreground); }
      .border-accent-foreground { border-color: var(--fishing-accent-foreground); }
      .ring-accent-foreground { --tw-ring-color: var(--fishing-accent-foreground); }
    </style>
    <style>
      .no-scrollbar::-webkit-scrollbar { display: none; }
      .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
      [data-icon] {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        -webkit-mask-size: contain;
        mask-size: contain;
        -webkit-mask-repeat: no-repeat;
        mask-repeat: no-repeat;
        -webkit-mask-position: center;
        mask-position: center;
        background-color: currentColor;
      }
    </style>
    <style id="page-styles">
/* ============================================
   垂钓文旅落地页 - 页面样式
   ============================================ */

* {
  box-sizing: border-box;
}

html {
  scroll-behavior: smooth;
}

body {
  font-family: var(--fishing-font-sans);
  background-color: var(--fishing-background);
  color: var(--fishing-foreground);
  margin: 0;
  padding: 0;
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
}

/* === 容器 === */
.container-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

/* === 导航栏 === */
.navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  padding: 20px 0;
  transition: all 0.3s ease;
  background: transparent;
}

.navbar.scrolled {
  background: rgba(250, 250, 247, 0.95);
  backdrop-filter: blur(12px);
  padding: 12px 0;
  box-shadow: 0 2px 20px rgba(45, 90, 61, 0.08);
}

.navbar-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.navbar-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 700;
  font-size: 18px;
  color: var(--fishing-primary-foreground);
  transition: color 0.3s ease;
}

.navbar.scrolled .navbar-logo {
  color: var(--fishing-primary);
}

.navbar-logo-icon {
  width: 36px;
  height: 36px;
  border-radius: var(--fishing-radius-md);
  background: var(--fishing-accent);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--fishing-accent-foreground);
}

.navbar.scrolled .navbar-logo-icon {
  background: var(--fishing-primary);
  color: var(--fishing-primary-foreground);
}

.navbar-links {
  display: flex;
  align-items: center;
  gap: 36px;
  list-style: none;
  margin: 0;
  padding: 0;
}

.navbar-links a {
  color: rgba(255, 255, 255, 0.9);
  text-decoration: none;
  font-size: 15px;
  font-weight: 500;
  transition: color 0.3s ease;
  position: relative;
}

.navbar.scrolled .navbar-links a {
  color: var(--fishing-foreground);
}

.navbar-links a:hover {
  color: var(--fishing-accent);
}

.navbar-links a::after {
  content: '';
  position: absolute;
  bottom: -4px;
  left: 0;
  width: 0;
  height: 2px;
  background: var(--fishing-accent);
  transition: width 0.3s ease;
}

.navbar-links a:hover::after {
  width: 100%;
}

.navbar-cta {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 22px;
  background: var(--fishing-accent);
  color: var(--fishing-accent-foreground);
  border: none;
  border-radius: var(--fishing-radius-md);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.3s ease;
}

.navbar.scrolled .navbar-cta {
  background: var(--fishing-primary);
  color: var(--fishing-primary-foreground);
}

.navbar-cta:hover {
  transform: translateX(3px);
  background: var(--fishing-accent-light);
}

.navbar.scrolled .navbar-cta:hover {
  background: var(--fishing-primary-light);
}

.navbar-mobile-toggle {
  display: none;
  background: none;
  border: none;
  color: var(--fishing-primary-foreground);
  cursor: pointer;
  padding: 8px;
}

.navbar.scrolled .navbar-mobile-toggle {
  color: var(--fishing-primary);
}

/* === Hero 英雄区 === */
.hero {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  overflow: hidden;
  color: var(--fishing-primary-foreground);
}

.hero-bg {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 0;
}

.hero-bg img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.hero-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(
    135deg,
    rgba(30, 62, 42, 0.85) 0%,
    rgba(45, 90, 61, 0.7) 50%,
    rgba(200, 149, 86, 0.3) 100%
  );
  z-index: 1;
}

.hero-content {
  position: relative;
  z-index: 2;
  padding-top: 100px;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 18px;
  background: rgba(200, 149, 86, 0.2);
  border: 1px solid rgba(200, 149, 86, 0.4);
  border-radius: 999px;
  font-size: 13px;
  font-weight: 500;
  color: var(--fishing-accent-light);
  margin-bottom: 28px;
  backdrop-filter: blur(8px);
}

.hero-badge-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--fishing-accent);
  animation: pulse-dot 2s ease-in-out infinite;
}

@keyframes pulse-dot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.6; transform: scale(1.3); }
}

.hero-title {
  font-size: clamp(36px, 5vw, 64px);
  font-weight: 700;
  line-height: 1.2;
  margin: 0 0 24px 0;
  letter-spacing: -0.5px;
}

.hero-title-accent {
  color: var(--fishing-accent);
}

.hero-subtitle {
  font-size: clamp(15px, 1.5vw, 18px);
  line-height: 1.8;
  color: rgba(255, 255, 255, 0.8);
  max-width: 600px;
  margin: 0 0 40px 0;
}

.hero-cta-group {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 60px;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 32px;
  background: var(--fishing-accent);
  color: var(--fishing-accent-foreground);
  border: none;
  border-radius: var(--fishing-radius-md);
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.3s ease;
}

.btn-primary:hover {
  background: var(--fishing-accent-light);
  transform: translateX(3px);
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 32px;
  background: transparent;
  color: var(--fishing-primary-foreground);
  border: 1.5px solid rgba(255, 255, 255, 0.3);
  border-radius: var(--fishing-radius-md);
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.3s ease;
}

.btn-secondary:hover {
  border-color: var(--fishing-accent);
  color: var(--fishing-accent);
  transform: translateX(3px);
}

.hero-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 32px;
  max-width: 700px;
}

.hero-stat-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.hero-stat-number {
  font-size: clamp(28px, 3vw, 40px);
  font-weight: 700;
  color: var(--fishing-accent);
  line-height: 1;
}

.hero-stat-label {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.7);
}

/* === 通用区块 === */
.section {
  padding: 100px 0;
}

.section-header {
  text-align: center;
  margin-bottom: 64px;
}

.section-label {
  display: inline-block;
  padding: 6px 16px;
  background: var(--fishing-primary-tint);
  color: var(--fishing-primary);
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 16px;
}

.section-title {
  font-size: clamp(28px, 3.5vw, 40px);
  font-weight: 700;
  color: var(--fishing-foreground);
  margin: 0 0 16px 0;
  line-height: 1.3;
}

.section-desc {
  font-size: 16px;
  color: var(--fishing-muted-foreground);
  max-width: 600px;
  margin: 0 auto;
  line-height: 1.8;
}

/* === 滚动渐入动画 === */
.reveal {
  opacity: 0;
  transform: translateY(30px);
  transition: opacity 0.8s ease, transform 0.8s ease;
}

.reveal.visible {
  opacity: 1;
  transform: translateY(0);
}

.reveal-delay-1 { transition-delay: 0.1s; }
.reveal-delay-2 { transition-delay: 0.2s; }
.reveal-delay-3 { transition-delay: 0.3s; }
.reveal-delay-4 { transition-delay: 0.4s; }
.reveal-delay-5 { transition-delay: 0.5s; }
.reveal-delay-6 { transition-delay: 0.6s; }

/* === 痛点区块 === */
.pain-section {
  background: var(--fishing-card);
}

.pain-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
}

.pain-card {
  background: var(--fishing-background);
  border: 1px solid var(--fishing-border);
  border-radius: var(--fishing-radius-lg);
  padding: 36px 28px;
  transition: all 0.4s ease;
  position: relative;
  overflow: hidden;
}

.pain-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: var(--fishing-primary);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.4s ease;
}

.pain-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px rgba(45, 90, 61, 0.12);
  border-color: var(--fishing-primary);
}

.pain-card:hover::before {
  transform: scaleX(1);
}

.pain-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--fishing-radius-md);
  background: var(--fishing-primary-tint);
  color: var(--fishing-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24px;
  transition: all 0.3s ease;
}

.pain-card:hover .pain-icon {
  background: var(--fishing-primary);
  color: var(--fishing-primary-foreground);
}

.pain-card h3 {
  font-size: 18px;
  font-weight: 600;
  color: var(--fishing-foreground);
  margin: 0 0 12px 0;
}

.pain-card p {
  font-size: 14px;
  color: var(--fishing-muted-foreground);
  line-height: 1.7;
  margin: 0;
}

/* === 解决方案总览 === */
.solutions-section {
  background: var(--fishing-background);
}

.solutions-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 28px;
}

.solution-card {
  background: var(--fishing-card);
  border-radius: var(--fishing-radius-lg);
  overflow: hidden;
  border: 1px solid var(--fishing-border);
  transition: all 0.4s ease;
}

.solution-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 24px 48px rgba(45, 90, 61, 0.14);
}

.solution-image {
  position: relative;
  height: 220px;
  overflow: hidden;
}

.solution-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s ease;
}

.solution-card:hover .solution-image img {
  transform: scale(1.08);
}

.solution-image-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 20px;
  background: linear-gradient(transparent, rgba(30, 62, 42, 0.9));
}

.solution-tag {
  display: inline-block;
  padding: 4px 12px;
  background: var(--fishing-accent);
  color: var(--fishing-accent-foreground);
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

.solution-body {
  padding: 28px;
}

.solution-body h3 {
  font-size: 22px;
  font-weight: 700;
  color: var(--fishing-foreground);
  margin: 0 0 14px 0;
}

.solution-body p {
  font-size: 14px;
  color: var(--fishing-muted-foreground);
  line-height: 1.8;
  margin: 0 0 20px 0;
}

.solution-features {
  list-style: none;
  padding: 0;
  margin: 0 0 24px 0;
}

.solution-features li {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  color: var(--fishing-foreground);
  padding: 6px 0;
}

.solution-features li i {
  color: var(--fishing-primary);
  flex-shrink: 0;
}

.solution-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--fishing-primary);
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
}

.solution-link:hover {
  gap: 10px;
  color: var(--fishing-accent);
}

/* === 核心功能 === */
.features-section {
  background: var(--fishing-card);
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.feature-card {
  background: var(--fishing-background);
  border: 1px solid var(--fishing-border);
  border-radius: var(--fishing-radius-lg);
  padding: 36px 32px;
  transition: all 0.4s ease;
  position: relative;
}

.feature-card:hover {
  background: var(--fishing-card);
  transform: translateY(-4px);
  box-shadow: 0 16px 32px rgba(45, 90, 61, 0.1);
  border-color: var(--fishing-primary);
}

.feature-icon {
  width: 52px;
  height: 52px;
  border-radius: var(--fishing-radius-md);
  background: var(--fishing-accent-tint);
  color: var(--fishing-accent);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24px;
  transition: all 0.3s ease;
}

.feature-card:hover .feature-icon {
  background: var(--fishing-accent);
  color: var(--fishing-accent-foreground);
}

.feature-card h3 {
  font-size: 18px;
  font-weight: 600;
  color: var(--fishing-foreground);
  margin: 0 0 12px 0;
}

.feature-card p {
  font-size: 14px;
  color: var(--fishing-muted-foreground);
  line-height: 1.7;
  margin: 0;
}

/* === 应用场景 === */
.scenes-section {
  background: var(--fishing-background);
}

.scene-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 80px;
  align-items: center;
  margin-bottom: 80px;
}

.scene-row:last-child {
  margin-bottom: 0;
}

.scene-row.reverse .scene-image {
  order: 2;
}

.scene-row.reverse .scene-content {
  order: 1;
}

.scene-image {
  position: relative;
  border-radius: var(--fishing-radius-xl);
  overflow: hidden;
  aspect-ratio: 4/3;
}

.scene-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.scene-image::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(
    135deg,
    rgba(45, 90, 61, 0.1) 0%,
    transparent 60%
  );
}

.scene-decoration {
  position: absolute;
  width: 100px;
  height: 100px;
  border: 2px solid var(--fishing-accent);
  border-radius: var(--fishing-radius-lg);
  opacity: 0.3;
}

.scene-decoration.top-left {
  top: -16px;
  left: -16px;
  border-right: none;
  border-bottom: none;
}

.scene-decoration.bottom-right {
  bottom: -16px;
  right: -16px;
  border-left: none;
  border-top: none;
}

.scene-label {
  display: inline-block;
  padding: 6px 16px;
  background: var(--fishing-accent-tint);
  color: var(--fishing-accent);
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 20px;
}

.scene-content h2 {
  font-size: clamp(24px, 3vw, 34px);
  font-weight: 700;
  color: var(--fishing-foreground);
  margin: 0 0 20px 0;
  line-height: 1.3;
}

.scene-content > p {
  font-size: 15px;
  color: var(--fishing-muted-foreground);
  line-height: 1.9;
  margin: 0 0 28px 0;
}

.scene-points {
  list-style: none;
  padding: 0;
  margin: 0 0 32px 0;
}

.scene-points li {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 10px 0;
  font-size: 15px;
  color: var(--fishing-foreground);
}

.scene-points li i {
  color: var(--fishing-primary);
  flex-shrink: 0;
  margin-top: 2px;
}

/* === 客户案例 / 数据 === */
.cases-section {
  background: var(--fishing-primary-dark);
  color: var(--fishing-primary-foreground);
  position: relative;
  overflow: hidden;
}

.cases-section::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -20%;
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, rgba(200, 149, 86, 0.15) 0%, transparent 70%);
  border-radius: 50%;
}

.cases-section .section-title {
  color: var(--fishing-primary-foreground);
}

.cases-section .section-desc {
  color: rgba(255, 255, 255, 0.7);
}

.cases-section .section-label {
  background: rgba(200, 149, 86, 0.2);
  color: var(--fishing-accent-light);
}

.cases-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 40px;
  margin-bottom: 80px;
  text-align: center;
}

.case-stat {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.case-stat-number {
  font-size: clamp(32px, 4vw, 52px);
  font-weight: 700;
  color: var(--fishing-accent);
  line-height: 1;
}

.case-stat-label {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
}

.partners-title {
  text-align: center;
  font-size: 16px;
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 32px;
  font-weight: 500;
}

.partners-logos {
  display: flex;
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
  gap: 48px 64px;
  margin-bottom: 80px;
}

.partner-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 16px;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.6);
  transition: color 0.3s ease;
}

.partner-logo:hover {
  color: var(--fishing-accent);
}

.partner-logo-icon {
  width: 36px;
  height: 36px;
  border-radius: var(--fishing-radius-sm);
  background: rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
}

.testimonial {
  max-width: 800px;
  margin: 0 auto;
  text-align: center;
  padding: 48px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: var(--fishing-radius-xl);
  border: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
}

.testimonial-quote {
  font-size: 18px;
  line-height: 1.9;
  color: rgba(255, 255, 255, 0.85);
  margin: 0 0 28px 0;
  font-style: normal;
}

.testimonial-author {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
}

.testimonial-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: var(--fishing-accent);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--fishing-accent-foreground);
  font-weight: 700;
  font-size: 16px;
}

.testimonial-info {
  text-align: left;
}

.testimonial-name {
  font-size: 15px;
  font-weight: 600;
  color: var(--fishing-primary-foreground);
}

.testimonial-role {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.6);
}

/* === 联系咨询 CTA === */
.contact-section {
  background: var(--fishing-background);
}

.contact-wrapper {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 80px;
  align-items: center;
  background: var(--fishing-card);
  border-radius: var(--fishing-radius-xl);
  padding: 64px;
  box-shadow: 0 20px 60px rgba(45, 90, 61, 0.08);
  border: 1px solid var(--fishing-border);
}

.contact-info h2 {
  font-size: clamp(28px, 3.5vw, 40px);
  font-weight: 700;
  color: var(--fishing-foreground);
  margin: 0 0 20px 0;
  line-height: 1.3;
}

.contact-info > p {
  font-size: 15px;
  color: var(--fishing-muted-foreground);
  line-height: 1.9;
  margin: 0 0 36px 0;
}

.contact-methods {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.contact-method {
  display: flex;
  align-items: center;
  gap: 14px;
}

.contact-method-icon {
  width: 44px;
  height: 44px;
  border-radius: var(--fishing-radius-md);
  background: var(--fishing-primary-tint);
  color: var(--fishing-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.contact-method-text {
  display: flex;
  flex-direction: column;
}

.contact-method-label {
  font-size: 13px;
  color: var(--fishing-muted-foreground);
}

.contact-method-value {
  font-size: 15px;
  font-weight: 600;
  color: var(--fishing-foreground);
}

.contact-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-size: 14px;
  font-weight: 500;
  color: var(--fishing-foreground);
}

.form-group input,
.form-group textarea {
  padding: 12px 16px;
  border: 1px solid var(--fishing-border);
  border-radius: var(--fishing-radius-md);
  background: var(--fishing-background);
  color: var(--fishing-foreground);
  font-size: 14px;
  font-family: inherit;
  transition: all 0.3s ease;
  outline: none;
}

.form-group input:focus,
.form-group textarea:focus {
  border-color: var(--fishing-primary);
  box-shadow: 0 0 0 3px var(--fishing-primary-tint);
}

.form-group textarea {
  resize: vertical;
  min-height: 100px;
}

.btn-submit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 14px 32px;
  background: var(--fishing-primary);
  color: var(--fishing-primary-foreground);
  border: none;
  border-radius: var(--fishing-radius-md);
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  width: 100%;
}

.btn-submit:hover {
  background: var(--fishing-primary-light);
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(45, 90, 61, 0.25);
}

/* === 页脚 === */
.footer {
  background: var(--fishing-foreground);
  color: var(--fishing-primary-foreground);
  padding: 72px 0 32px 0;
}

.footer-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 48px;
  margin-bottom: 48px;
}

.footer-brand {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.footer-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 700;
  font-size: 18px;
  color: var(--fishing-primary-foreground);
}

.footer-logo-icon {
  width: 36px;
  height: 36px;
  border-radius: var(--fishing-radius-md);
  background: var(--fishing-accent);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--fishing-accent-foreground);
}

.footer-brand p {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.6);
  line-height: 1.8;
  margin: 0;
  max-width: 320px;
}

.footer-social {
  display: flex;
  gap: 12px;
}

.footer-social a {
  width: 36px;
  height: 36px;
  border-radius: var(--fishing-radius-sm);
  background: rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255, 255, 255, 0.7);
  transition: all 0.3s ease;
}

.footer-social a:hover {
  background: var(--fishing-accent);
  color: var(--fishing-accent-foreground);
}

.footer-column h4 {
  font-size: 15px;
  font-weight: 600;
  color: var(--fishing-primary-foreground);
  margin: 0 0 20px 0;
}

.footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.footer-links a {
  color: rgba(255, 255, 255, 0.6);
  text-decoration: none;
  font-size: 14px;
  transition: color 0.3s ease;
}

.footer-links a:hover {
  color: var(--fishing-accent);
}

.footer-bottom {
  padding-top: 32px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.footer-bottom p {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.4);
  margin: 0;
}

.footer-bottom-links {
  display: flex;
  gap: 24px;
}

.footer-bottom-links a {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.4);
  text-decoration: none;
  transition: color 0.3s ease;
}

.footer-bottom-links a:hover {
  color: var(--fishing-accent);
}

/* === 图片淡入 === */
.img-fade {
  opacity: 0;
  transition: opacity 0.8s ease;
}

.img-fade.loaded {
  opacity: 1;
}

/* === 响应式 === */
@media (max-width: 1024px) {
  .pain-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .solutions-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .features-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .footer-grid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 768px) {
  .section {
    padding: 64px 0;
  }

  .navbar-links {
    display: none;
  }

  .navbar-cta {
    display: none;
  }

  .navbar-mobile-toggle {
    display: flex;
  }

  .hero-stats {
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
  }

  .pain-grid {
    grid-template-columns: 1fr;
  }

  .solutions-grid {
    grid-template-columns: 1fr;
  }

  .features-grid {
    grid-template-columns: 1fr;
  }

  .scene-row,
  .scene-row.reverse {
    grid-template-columns: 1fr;
    gap: 40px;
  }

  .scene-row.reverse .scene-image {
    order: 0;
  }

  .scene-row.reverse .scene-content {
    order: 0;
  }

  .cases-stats {
    grid-template-columns: repeat(2, 1fr);
    gap: 32px;
  }

  .contact-wrapper {
    grid-template-columns: 1fr;
    gap: 48px;
    padding: 40px 24px;
  }

  .footer-grid {
    grid-template-columns: 1fr;
    gap: 32px;
  }

  .footer-bottom {
    flex-direction: column;
    text-align: center;
  }

  .partners-logos {
    gap: 32px 40px;
  }

  .testimonial {
    padding: 32px 24px;
  }
}

@media (max-width: 480px) {
  .container-page {
    padding: 0 16px;
  }

  .hero-cta-group {
    flex-direction: column;
  }

  .btn-primary,
  .btn-secondary {
    width: 100%;
    justify-content: center;
  }

  .hero-stats {
    grid-template-columns: 1fr 1fr;
    gap: 20px;
  }
}

/* === 减少动画偏好 === */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }

  .reveal {
    opacity: 1;
    transform: none;
  }
}
    </style>
</head>
<body class="min-h-screen font-sans antialiased">
    <main>
        <!-- ========== 导航栏 ========== -->
        <nav class="navbar" id="navbar">
            <div class="container-page navbar-inner">
                <a href="#" class="navbar-logo">
                    <span class="navbar-logo-icon">
                        <i data-lucide="fish" style="width:20px;height:20px;"></i>
                    </span>
                    微渔小助
                </a>
                <ul class="navbar-links">
                    <li><a href="#solutions">解决方案</a></li>
                    <li><a href="#features">核心功能</a></li>
                    <li><a href="#scenes">应用场景</a></li>
                    <li><a href="#cases">客户案例</a></li>
                    <li><a href="#contact">联系我们</a></li>
                </ul>
                <a href="#contact" class="navbar-cta" data-dom-id="nav-cta-contact">
                    免费咨询
                    <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
                </a>
                <button class="navbar-mobile-toggle" aria-label="菜单">
                    <i data-lucide="menu" style="width:24px;height:24px;"></i>
                </button>
            </div>
        </nav>

        <!-- ========== Hero 英雄区 ========== -->
        <section class="hero" id="hero">
            <div class="hero-bg">
                <img src="/images/fishing/hero-fishing-scene.jpg" alt="湖面日出垂钓" class="img-fade" onload="this.classList.add('loaded')">
            </div>
            <div class="hero-overlay"></div>
            <div class="container-page hero-content">
                <div class="hero-badge reveal">
                    <span class="hero-badge-dot"></span>
                    休闲渔业数字化升级
                </div>
                <h1 class="hero-title reveal reveal-delay-1">
                    垂钓文旅<br>
                    <span class="hero-title-accent">数字化解决方案</span>
                </h1>
                <p class="hero-subtitle reveal reveal-delay-2">
                    为钓场、赛事、文旅景区提供全链路数字化经营工具，助力休闲渔业转型升级
                </p>
                <div class="hero-cta-group reveal reveal-delay-3">
                    <a href="#contact" class="btn-primary" data-dom-id="hero-cta-contact">
                        免费试用
                        <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                    </a>
                    <a href="#solutions" class="btn-secondary" data-dom-id="hero-cta-demo">
                        <i data-lucide="play-circle" style="width:16px;height:16px;"></i>
                        了解方案
                    </a>
                </div>
                <div class="hero-stats reveal reveal-delay-4">
                    <div class="hero-stat-item">
                        <span class="hero-stat-number" data-count="500">0</span>
                        <span class="hero-stat-label">合作钓场</span>
                    </div>
                    <div class="hero-stat-item">
                        <span class="hero-stat-number" data-count="1200">0</span>
                        <span class="hero-stat-label">赛事场次</span>
                    </div>
                    <div class="hero-stat-item">
                        <span class="hero-stat-number" data-count="50">0</span>
                        <span class="hero-stat-label">文旅景区</span>
                    </div>
                    <div class="hero-stat-item">
                        <span class="hero-stat-number" data-count="98">0</span>
                        <span class="hero-stat-label">客户满意度 %</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========== 行业痛点 ========== -->
        <section class="section pain-section" id="pain">
            <div class="container-page">
                <div class="section-header">
                    <span class="section-label reveal">行业痛点</span>
                    <h2 class="section-title reveal reveal-delay-1">垂钓经营面临的四大挑战</h2>
                    <p class="section-desc reveal reveal-delay-2">
                        传统钓场与赛事运营模式效率低下，数字化程度不足，制约行业发展
                    </p>
                </div>
                <div class="pain-grid">
                    <div class="pain-card reveal reveal-delay-1">
                        <div class="pain-icon">
                            <i data-lucide="clock" style="width:24px;height:24px;"></i>
                        </div>
                        <h3>运营效率低</h3>
                        <p>人工登记、电话预约、现金收费等传统方式耗时耗力，高峰期排号混乱，客户体验差。</p>
                    </div>
                    <div class="pain-card reveal reveal-delay-2">
                        <div class="pain-icon">
                            <i data-lucide="trending-down" style="width:24px;height:24px;"></i>
                        </div>
                        <h3>获客成本高</h3>
                        <p>依赖口碑传播和本地社群，缺乏精准营销手段，新客增长缓慢，复购率难以提升。</p>
                    </div>
                    <div class="pain-card reveal reveal-delay-3">
                        <div class="pain-icon">
                            <i data-lucide="users" style="width:24px;height:24px;"></i>
                        </div>
                        <h3>会员管理难</h3>
                        <p>会员信息分散，权益体系缺失，无法精准分层运营，高价值客户流失严重。</p>
                    </div>
                    <div class="pain-card reveal reveal-delay-4">
                        <div class="pain-icon">
                            <i data-lucide="bar-chart-2" style="width:24px;height:24px;"></i>
                        </div>
                        <h3>数据无沉淀</h3>
                        <p>经营数据散落在各处，缺乏统一分析维度，决策依赖经验，增长瓶颈难以突破。</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========== 解决方案总览 ========== -->
        <section class="section solutions-section" id="solutions">
            <div class="container-page">
                <div class="section-header">
                    <span class="section-label reveal">解决方案</span>
                    <h2 class="section-title reveal reveal-delay-1">三大核心方案，覆盖全业务场景</h2>
                    <p class="section-desc reveal reveal-delay-2">
                        从钓场经营到赛事运营，从会员管理到文旅融合，一站式数字化升级
                    </p>
                </div>
                <div class="solutions-grid">
                    <div class="solution-card reveal reveal-delay-1">
                        <div class="solution-image">
                            <img src="/images/fishing/scene-pond.jpg" alt="智慧钓场" class="img-fade" onload="this.classList.add('loaded')">
                            <div class="solution-image-overlay">
                                <span class="solution-tag">智慧经营</span>
                            </div>
                        </div>
                        <div class="solution-body">
                            <h3>智慧钓场</h3>
                            <p>为钓场经营者提供全流程数字化管理工具，提升运营效率，降低人力成本。</p>
                            <ul class="solution-features">
                                <li>
                                    <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
                                    在线预订与智能排号
                                </li>
                                <li>
                                    <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
                                    票务核销与收银管理
                                </li>
                                <li>
                                    <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
                                    会员体系与积分商城
                                </li>
                            </ul>
                            <a href="#contact" class="solution-link">
                                了解详情
                                <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
                            </a>
                        </div>
                    </div>
                    <div class="solution-card reveal reveal-delay-2">
                        <div class="solution-image">
                            <img src="/images/fishing/scene-competition.jpg" alt="赛事管理" class="img-fade" onload="this.classList.add('loaded')">
                            <div class="solution-image-overlay">
                                <span class="solution-tag">赛事运营</span>
                            </div>
                        </div>
                        <div class="solution-body">
                            <h3>赛事管理</h3>
                            <p>专业的钓鱼赛事报名、抽签、计分、排名全流程系统，让赛事组织更专业高效。</p>
                            <ul class="solution-features">
                                <li>
                                    <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
                                    在线报名与费用管理
                                </li>
                                <li>
                                    <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
                                    智能抽签与分区安排
                                </li>
                                <li>
                                    <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
                                    实时计分与成绩公示
                                </li>
                            </ul>
                            <a href="#contact" class="solution-link">
                                了解详情
                                <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
                            </a>
                        </div>
                    </div>
                    <div class="solution-card reveal reveal-delay-3">
                        <div class="solution-image">
                            <img src="/images/fishing/hero-fishing-scene.jpg" alt="文旅融合" class="img-fade" onload="this.classList.add('loaded')">
                            <div class="solution-image-overlay">
                                <span class="solution-tag">文旅升级</span>
                            </div>
                        </div>
                        <div class="solution-body">
                            <h3>文旅融合</h3>
                            <p>助力文旅景区打造垂钓特色IP，串联吃住行游购娱，提升景区综合收益。</p>
                            <ul class="solution-features">
                                <li>
                                    <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
                                    垂钓+文旅套餐产品
                                </li>
                                <li>
                                    <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
                                    多业态联合营销
                                </li>
                                <li>
                                    <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
                                    全域数据打通分析
                                </li>
                            </ul>
                            <a href="#contact" class="solution-link">
                                了解详情
                                <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========== 核心功能 ========== -->
        <section class="section features-section" id="features">
            <div class="container-page">
                <div class="section-header">
                    <span class="section-label reveal">核心功能</span>
                    <h2 class="section-title reveal reveal-delay-1">六大功能模块，覆盖经营全链路</h2>
                    <p class="section-desc reveal reveal-delay-2">
                        从前端获客到后端管理，从线上预订到线下核销，功能完善开箱即用
                    </p>
                </div>
                <div class="features-grid">
                    <div class="feature-card reveal reveal-delay-1">
                        <div class="feature-icon">
                            <i data-lucide="calendar-check" style="width:24px;height:24px;"></i>
                        </div>
                        <h3>在线预订</h3>
                        <p>支持微信小程序、公众号多渠道预订，钓位实时可视化，订单状态自动同步。</p>
                    </div>
                    <div class="feature-card reveal reveal-delay-2">
                        <div class="feature-icon">
                            <i data-lucide="list-ordered" style="width:24px;height:24px;"></i>
                        </div>
                        <h3>智能排号</h3>
                        <p>高峰期自动排号叫号，支持预约优先与现场排队双通道，减少客户等待焦虑。</p>
                    </div>
                    <div class="feature-card reveal reveal-delay-3">
                        <div class="feature-icon">
                            <i data-lucide="crown" style="width:24px;height:24px;"></i>
                        </div>
                        <h3>会员体系</h3>
                        <p>多等级会员权益，储值卡、次卡、年卡灵活配置，精准营销提升复购率。</p>
                    </div>
                    <div class="feature-card reveal reveal-delay-4">
                        <div class="feature-icon">
                            <i data-lucide="ticket" style="width:24px;height:24px;"></i>
                        </div>
                        <h3>赛事报名</h3>
                        <p>一键发布赛事，在线报名缴费，自动分组抽签，成绩实时排名公示。</p>
                    </div>
                    <div class="feature-card reveal reveal-delay-5">
                        <div class="feature-icon">
                            <i data-lucide="qr-code" style="width:24px;height:24px;"></i>
                        </div>
                        <h3>票务管理</h3>
                        <p>多票种配置，二维码核销，支持退票改签，财务对账清晰透明。</p>
                    </div>
                    <div class="feature-card reveal reveal-delay-6">
                        <div class="feature-icon">
                            <i data-lucide="pie-chart" style="width:24px;height:24px;"></i>
                        </div>
                        <h3>数据看板</h3>
                        <p>经营数据可视化，营收、客流、会员、赛事多维度分析，助力科学决策。</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========== 应用场景 ========== -->
        <section class="section scenes-section" id="scenes">
            <div class="container-page">
                <div class="section-header">
                    <span class="section-label reveal">应用场景</span>
                    <h2 class="section-title reveal reveal-delay-1">深耕垂钓行业，适配多元经营场景</h2>
                    <p class="section-desc reveal reveal-delay-2">
                        无论你是钓场经营者、赛事主办方还是文旅景区，都能找到合适的解决方案
                    </p>
                </div>

                <!-- 场景一：钓场运营 -->
                <div class="scene-row">
                    <div class="scene-image reveal">
                        <span class="scene-decoration top-left"></span>
                        <img src="/images/fishing/scene-pond.jpg" alt="钓场运营" class="img-fade" onload="this.classList.add('loaded')">
                        <span class="scene-decoration bottom-right"></span>
                    </div>
                    <div class="scene-content reveal reveal-delay-2">
                        <span class="scene-label">钓场运营</span>
                        <h2>从预约到离场，全流程数字化管理</h2>
                        <p>
                            针对不同规模的钓场提供定制化解决方案，无论是小型休闲钓场还是大型垂钓度假村，
                            都能通过系统实现高效运营，降低人力成本，提升客户满意度。
                        </p>
                        <ul class="scene-points">
                            <li>
                                <i data-lucide="check-circle" style="width:18px;height:18px;"></i>
                                钓位实时状态可视化，客户在线选位预订
                            </li>
                            <li>
                                <i data-lucide="check-circle" style="width:18px;height:18px;"></i>
                                多种计费模式支持：按时、按次、按天、会员制
                            </li>
                            <li>
                                <i data-lucide="check-circle" style="width:18px;height:18px;"></i>
                                装备租赁、餐饮等周边服务一体化管理
                            </li>
                            <li>
                                <i data-lucide="check-circle" style="width:18px;height:18px;"></i>
                                渔获记录与排行榜，增强用户粘性与社交传播
                            </li>
                        </ul>
                        <a href="#contact" class="btn-primary">
                            预约演示
                            <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                        </a>
                    </div>
                </div>

                <!-- 场景二：赛事举办 -->
                <div class="scene-row reverse">
                    <div class="scene-image reveal">
                        <span class="scene-decoration top-left"></span>
                        <img src="/images/fishing/scene-competition.jpg" alt="赛事举办" class="img-fade" onload="this.classList.add('loaded')">
                        <span class="scene-decoration bottom-right"></span>
                    </div>
                    <div class="scene-content reveal reveal-delay-2">
                        <span class="scene-label">赛事举办</span>
                        <h2>专业赛事系统，让组织更轻松</h2>
                        <p>
                            为各级钓鱼赛事提供完整的数字化解决方案，从报名宣传到成绩公示，
                            全流程线上化管理，提升赛事专业度与参与体验。
                        </p>
                        <ul class="scene-points">
                            <li>
                                <i data-lucide="check-circle" style="width:18px;height:18px;"></i>
                                赛事一键发布，自定义报名条件与费用标准
                            </li>
                            <li>
                                <i data-lucide="check-circle" style="width:18px;height:18px;"></i>
                                智能抽签分区，支持多赛制、多组别配置
                            </li>
                            <li>
                                <i data-lucide="check-circle" style="width:18px;height:18px;"></i>
                                移动端实时录分，成绩自动排名同步公示
                            </li>
                            <li>
                                <i data-lucide="check-circle" style="width:18px;height:18px;"></i>
                                赛事数据沉淀，参赛选手档案长期留存
                            </li>
                        </ul>
                        <a href="#contact" class="btn-primary">
                            预约演示
                            <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========== 客户案例 / 数据 ========== -->
        <section class="section cases-section" id="cases">
            <div class="container-page">
                <div class="section-header">
                    <span class="section-label reveal">客户案例</span>
                    <h2 class="section-title reveal reveal-delay-1">携手行业伙伴，共创垂钓新未来</h2>
                    <p class="section-desc reveal reveal-delay-2">
                        已服务全国数百家钓场、赛事主办方与文旅景区，持续创造价值
                    </p>
                </div>

                <div class="cases-stats reveal reveal-delay-1">
                    <div class="case-stat">
                        <span class="case-stat-number" data-count="500">0</span>
                        <span class="case-stat-label">合作钓场</span>
                    </div>
                    <div class="case-stat">
                        <span class="case-stat-number" data-count="1200">0</span>
                        <span class="case-stat-label">累计赛事</span>
                    </div>
                    <div class="case-stat">
                        <span class="case-stat-number" data-count="500000">0</span>
                        <span class="case-stat-label">服务钓友</span>
                    </div>
                    <div class="case-stat">
                        <span class="case-stat-number" data-count="35">0</span>
                        <span class="case-stat-label">覆盖省份</span>
                    </div>
                </div>

                <p class="partners-title reveal">合作品牌与机构</p>
                <div class="partners-logos reveal reveal-delay-1">
                    <div class="partner-logo">
                        <span class="partner-logo-icon">
                            <i data-lucide="fish" style="width:18px;height:18px;"></i>
                        </span>
                        渔乐汇
                    </div>
                    <div class="partner-logo">
                        <span class="partner-logo-icon">
                            <i data-lucide="mountain" style="width:18px;height:18px;"></i>
                        </span>
                        青山湖钓场
                    </div>
                    <div class="partner-logo">
                        <span class="partner-logo-icon">
                            <i data-lucide="trophy" style="width:18px;height:18px;"></i>
                        </span>
                        金龙杯赛事
                    </div>
                    <div class="partner-logo">
                        <span class="partner-logo-icon">
                            <i data-lucide="trees" style="width:18px;height:18px;"></i>
                        </span>
                        绿野度假区
                    </div>
                    <div class="partner-logo">
                        <span class="partner-logo-icon">
                            <i data-lucide="waves" style="width:18px;height:18px;"></i>
                        </span>
                        碧水湾
                    </div>
                    <div class="partner-logo">
                        <span class="partner-logo-icon">
                            <i data-lucide="anchor" style="width:18px;height:18px;"></i>
                        </span>
                        海钓协会
                    </div>
                </div>

                <div class="testimonial reveal reveal-delay-2">
                    <p class="testimonial-quote">
                        "接入微渔小助系统后，我们钓场的运营效率提升了60%，会员复购率增长了45%。
                        特别是在线预订和智能排号功能，彻底解决了高峰期的混乱局面，客户满意度大幅提升。"
                    </p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">张</div>
                        <div class="testimonial-info">
                            <div class="testimonial-name">张建国</div>
                            <div class="testimonial-role">青山湖国际钓场 总经理</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========== 联系咨询 CTA ========== -->
        <section class="section contact-section" id="contact">
            <div class="container-page">
                <div class="contact-wrapper">
                    <div class="contact-info reveal">
                        <span class="section-label">立即咨询</span>
                        <h2>开启垂钓数字化升级之旅</h2>
                        <p>
                            留下您的联系方式，我们的行业顾问将在24小时内与您取得联系，
                            为您量身定制专属的数字化解决方案。
                        </p>
                        <div class="contact-methods">
                            <div class="contact-method">
                                <div class="contact-method-icon">
                                    <i data-lucide="phone" style="width:20px;height:20px;"></i>
                                </div>
                                <div class="contact-method-text">
                                    <span class="contact-method-label">咨询热线</span>
                                    <span class="contact-method-value">15799594125</span>
                                </div>
                            </div>
                            <div class="contact-method">
                                <div class="contact-method-icon">
                                    <i data-lucide="mail" style="width:20px;height:20px;"></i>
                                </div>
                                <div class="contact-method-text">
                                    <span class="contact-method-label">商务邮箱</span>
                                    <span class="contact-method-value">3664839@qq.com</span>
                                </div>
                            </div>
                            <div class="contact-method">
                                <div class="contact-method-icon">
                                    <i data-lucide="map-pin" style="width:20px;height:20px;"></i>
                                </div>
                                <div class="contact-method-text">
                                    <span class="contact-method-label">公司地址</span>
                                    <span class="contact-method-value">深圳市南山深圳湾科技生态园区</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <form class="contact-form reveal reveal-delay-2" onsubmit="event.preventDefault(); alert('提交成功，我们将尽快与您联系！');">
                        <div class="form-group">
                            <label for="name">姓名</label>
                            <input type="text" id="name" placeholder="请输入您的姓名" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">联系电话</label>
                            <input type="tel" id="phone" placeholder="请输入您的联系电话" required>
                        </div>
                        <div class="form-group">
                            <label for="demand">需求描述</label>
                            <textarea id="demand" placeholder="请简要描述您的需求，如钓场规模、期望功能等" required></textarea>
                        </div>
                        <button type="submit" class="btn-submit">
                            提交咨询
                            <i data-lucide="send" style="width:16px;height:16px;"></i>
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- ========== 页脚 ========== -->
        <footer class="footer">
            <div class="container-page">
                <div class="footer-grid">
                    <div class="footer-brand">
                        <div class="footer-logo">
                            <span class="footer-logo-icon">
                                <i data-lucide="fish" style="width:20px;height:20px;"></i>
                            </span>
                            微渔小助
                        </div>
                        <p>
                            专注于垂钓文旅行业数字化解决方案，为钓场经营者、赛事主办方、文旅景区
                            提供全链路经营工具，助力休闲渔业转型升级。
                        </p>
                        <div class="footer-social">
                            <a href="#" aria-label="微信">
                                <i data-lucide="message-circle" style="width:18px;height:18px;"></i>
                            </a>
                            <a href="#" aria-label="微博">
                                <i data-lucide="globe" style="width:18px;height:18px;"></i>
                            </a>
                            <a href="#" aria-label="抖音">
                                <i data-lucide="music" style="width:18px;height:18px;"></i>
                            </a>
                        </div>
                    </div>
                    <div class="footer-column">
                        <h4>产品方案</h4>
                        <ul class="footer-links">
                            <li><a href="#solutions">智慧钓场</a></li>
                            <li><a href="#solutions">赛事管理</a></li>
                            <li><a href="#solutions">文旅融合</a></li>
                            <li><a href="#features">功能模块</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>关于我们</h4>
                        <ul class="footer-links">
                            <li><a href="#">公司介绍</a></li>
                            <li><a href="#cases">客户案例</a></li>
                            <li><a href="#">新闻动态</a></li>
                            <li><a href="#">加入我们</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>联系方式</h4>
                        <ul class="footer-links">
                            <li><a href="tel:400-888-6666">15799594125</a></li>
                            <li><a href="mailto:contact@weiyuxiaozhu.com">3664839@qq.com</a></li>
                            <li><a href="#">深圳市南山深圳湾科技生态园区</a></li>
                            <li><a href="#contact">在线咨询</a></li>
                        </ul>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p>&copy; 2026 微渔小助 版权所有 <a href="https://beian.miit.gov.cn/" target="_blank"> 网站备案:浙ICP备14017126号-4 </a></p>
                    <div class="footer-bottom-links">
                        <a href="#">隐私政策</a>
                        <a href="#">服务条款</a>
                        <a href="#">帮助中心</a>
                    </div>
                </div>
            </div>
        </footer>
    </main>
    <script>
        // 导航栏滚动效果
        (function() {
            var navbar = document.getElementById('navbar');
            var lastScroll = 0;

            window.addEventListener('scroll', function() {
                var currentScroll = window.pageYOffset;

                if (currentScroll > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }

                lastScroll = currentScroll;
            });
        })();

        // 滚动渐入动画 (IntersectionObserver)
        (function() {
            var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (prefersReducedMotion) return;

            var revealElements = document.querySelectorAll('.reveal');

            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.1,
                    rootMargin: '0px 0px -50px 0px'
                });

                revealElements.forEach(function(el) {
                    observer.observe(el);
                });
            } else {
                // 降级：直接显示
                revealElements.forEach(function(el) {
                    el.classList.add('visible');
                });
            }
        })();

        // 数字计数动画
        (function() {
            var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            function animateCount(element) {
                var target = parseInt(element.getAttribute('data-count'), 10);
                var duration = 2000;
                var start = 0;
                var startTime = null;

                function easeOutExpo(t) {
                    return t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
                }

                function step(timestamp) {
                    if (!startTime) startTime = timestamp;
                    var progress = Math.min((timestamp - startTime) / duration, 1);
                    var easedProgress = easeOutExpo(progress);
                    var current = Math.floor(easedProgress * target);

                    if (target >= 10000) {
                        element.textContent = (current / 10000).toFixed(1) + '万';
                    } else {
                        element.textContent = current.toLocaleString();
                    }

                    if (progress < 1) {
                        requestAnimationFrame(step);
                    } else {
                        if (target >= 10000) {
                            element.textContent = (target / 10000).toFixed(1) + '万';
                        } else {
                            element.textContent = target.toLocaleString();
                        }
                    }
                }

                requestAnimationFrame(step);
            }

            var countElements = document.querySelectorAll('[data-count]');
            var counted = false;

            function checkAndAnimate() {
                if (counted) return;

                var heroStats = document.querySelector('.hero-stats');
                if (!heroStats) {
                    counted = true;
                    countElements.forEach(function(el) {
                        if (prefersReducedMotion) {
                            var target = parseInt(el.getAttribute('data-count'), 10);
                            if (target >= 10000) {
                                el.textContent = (target / 10000).toFixed(1) + '万';
                            } else {
                                el.textContent = target.toLocaleString();
                            }
                        } else {
                            animateCount(el);
                        }
                    });
                    return;
                }

                var rect = heroStats.getBoundingClientRect();
                var isVisible = rect.top < window.innerHeight && rect.bottom > 0;

                if (isVisible) {
                    counted = true;
                    countElements.forEach(function(el) {
                        if (prefersReducedMotion) {
                            var target = parseInt(el.getAttribute('data-count'), 10);
                            if (target >= 10000) {
                                el.textContent = (target / 10000).toFixed(1) + '万';
                            } else {
                                el.textContent = target.toLocaleString();
                            }
                        } else {
                            animateCount(el);
                        }
                    });
                }
            }

            // 初始检查
            checkAndAnimate();

            // 滚动检查
            window.addEventListener('scroll', checkAndAnimate);
        })();

        // 平滑滚动
        (function() {
            var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (prefersReducedMotion) return;

            document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
                anchor.addEventListener('click', function(e) {
                    var targetId = this.getAttribute('href');
                    if (targetId === '#') return;

                    var targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        e.preventDefault();
                        var offsetTop = targetElement.offsetTop - 70;
                        window.scrollTo({
                            top: offsetTop,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        })();

        // 初始化图标
        lucide.createIcons();
    </script>
</body>
</html>
